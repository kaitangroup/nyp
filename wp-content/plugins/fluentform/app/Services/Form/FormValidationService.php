<?php

namespace FluentForm\App\Services\Form;

use FluentForm\App\Helpers\Helper;
use FluentForm\App\Models\FormMeta;
use FluentForm\App\Modules\Form\AkismetHandler;
use FluentForm\App\Modules\Form\CleanTalkHandler;
use FluentForm\App\Modules\Form\FormDataParser;
use FluentForm\App\Modules\Form\FormFieldsParser;
use FluentForm\App\Modules\HCaptcha\HCaptcha;
use FluentForm\App\Modules\ReCaptcha\ReCaptcha;
use FluentForm\App\Modules\Turnstile\Turnstile;
use FluentForm\App\Services\FormBuilder\Components\SelectCountry;
use FluentForm\Framework\Foundation\App;
use FluentForm\Framework\Helpers\ArrayHelper as Arr;
use FluentForm\Framework\Validator\ValidationException;

class FormValidationService
{
    /** Skip a provider that just failed rather than re-timing-out per submission. */
    const GEO_BACKOFF_MINUTES = 15;

    const GEO_TIMEOUT = 3;

    /** Resolved countries are reused so a flood cannot burn provider quota. */
    const GEO_CACHE_MINUTES = 10;

    /** Entries per cache shard; 256 shards keeps rows small and uncontended. */
    const GEO_CACHE_SHARD_MAX = 25;

    /** Consecutive inconclusive answers before a provider is treated as down. */
    const GEO_PROVIDER_STRIKES = 3;

    protected $app;
    protected $form;
    protected $formData;

    public function __construct()
    {
        $this->app = App::getInstance();
    }

    public function setForm($form)
    {
        $this->form = $form;
    }

    public function setFormData($formData)
    {
        $this->formData = $formData;
    }

    /**
     * @param $fields
     * @param $formData
     * @return bool
     * @throws ValidationException
     */
    public function validateSubmission(&$fields, &$formData)
    {
        do_action('fluentform/before_form_validation', $fields, $formData);

        $this->preventMaliciousAttacks();

        $this->validateRestrictions($fields);

        $this->validateNonce();

        $this->validateReCaptcha();
        $this->validateHCaptcha();
        $this->validateTurnstile();

        foreach ($fields as $fieldName => $field) {
            if (isset($formData[$fieldName])) {
                $element = $field['element'];

                $formData[$fieldName] = apply_filters_deprecated('fluentform_input_data_' . $element, [
                        $formData[$fieldName],
                        $field,
                        $formData,
                        $this->form
                    ],
                    FLUENTFORM_FRAMEWORK_UPGRADE,
                    'fluentform/input_data_' . $element,
                    'Use fluentform/input_data_' . $element . ' instead of fluentform_input_data_' . $element
                );
                $formData[$fieldName] = apply_filters('fluentform/input_data_' . $element, $formData[$fieldName], $field, $formData, $this->form);
            }
        }

        $originalValidations = FormFieldsParser::getValidations($this->form, $formData, $fields);

        // Fire an event so that one can hook into it to work with the rules & messages.
        $originalValidations = apply_filters_deprecated('fluentform_validations', [
                $originalValidations,
                $this->form,
                $formData
            ],
            FLUENTFORM_FRAMEWORK_UPGRADE,
            'fluentform/validations',
            'Use fluentform/validations instead of fluentform_validations.'
        );
        $validations = apply_filters('fluentform/validations', $originalValidations, $this->form, $formData);

        /*
         * Clean talk fix for now
         * They should not hook fluentform_validations and return nothing!
         * We will remove this extra check once it's done
         */
        if ($originalValidations && (!$validations || !array_filter($validations))) {
            $validations = $originalValidations;
        }

        $validator = wpFluentForm('validator')->make($formData, $validations[0], $validations[1]);

        $errors = [];
        if ($validator->validate()->fails()) {
            foreach ($validator->errors() as $attribute => $rules) {
                $position = strpos($attribute, ']');

                if ($position) {
                    $attribute = substr($attribute, 0, strpos($attribute, ']') + 1);
                }

                $errors[$attribute] = $rules;
            }
            // Fire an event so that one can hook into it to work with the errors.
            $errors = apply_filters_deprecated('fluentform_validation_error', [
                    $errors,
                    $this->form,
                    $fields,
                    $formData
                ],
                FLUENTFORM_FRAMEWORK_UPGRADE,
                'fluentform/validation_error',
                'Use fluentform/validation_error instead of fluentform_validation_error.'
            );

            $errors = $this->app->applyFilters('fluentform/validation_error', $errors, $this->form, $fields, $formData);
        }

        foreach ($fields as $fieldKey => $field) {
            $field['data_key'] = $fieldKey;
            $inputName = Arr::get($field, 'raw.attributes.name');
            $field['name'] = $inputName;
            $error = $this->validateInput($field, $formData, $this->form);

            // Deliberately here and not inside Helper::validateInput(): that
            // answers "is this value legal for this field" and is reused by entry
            // import, which would silently drop a historical row that breaches a
            // limit added later. How many options may be picked is a rule about
            // this submission, so it is enforced on this path only.
            if (!$error) {
                $error = Helper::validateSelectionLimits(
                    Arr::get($field, 'raw', $field),
                    Arr::get($formData, $inputName)
                );
            }
            $error = apply_filters_deprecated('fluentform_validate_input_item_' . $field['element'], [
                    $error,
                    $field,
                    $formData,
                    $fields,
                    $this->form,
                    $errors
                ],
                FLUENTFORM_FRAMEWORK_UPGRADE,
                'fluentform/validate_input_item_' . $field['element'],
                'Use fluentform/validate_input_item_' . $field['element'] . ' instead of fluentform_validate_input_item_' . $field['element']
            );

            $error = apply_filters('fluentform/validate_input_item_' . $field['element'], $error, $field, $formData, $fields, $this->form, $errors);
            if ($error) {
                if (empty($errors[$inputName])) {
                    $errors[$inputName] = [];
                }
                if (is_string($error)) {
                    $error = [fluentform_sanitize_html($error)];
                } else {
                    if (is_array($error)) {
                        foreach ($error as $rule => $message) {
                            $error[$rule] = fluentform_sanitize_html($message);
                        }
                    }
                }
                $errors[$inputName] = array_merge($error, $errors[$inputName]);
            }
        }

        $errors = apply_filters_deprecated('fluentform_validation_errors', [
                $errors,
                $formData,
                $this->form,
                $fields
            ],
            FLUENTFORM_FRAMEWORK_UPGRADE,
            'fluentform/validation_errors',
            'Use fluentform/validation_errors instead of fluentform_validation_errors.'
        );

        $errors = apply_filters('fluentform/validation_errors', $errors, $formData, $this->form, $fields);

        if ('yes' == Helper::getFormMeta($this->form->id, '_has_user_registration') && !get_current_user_id()) {
            $errors = apply_filters_deprecated('fluentform_validation_user_registration_errors', [
                    $errors,
                    $formData,
                    $this->form,
                    $fields
                ],
                FLUENTFORM_FRAMEWORK_UPGRADE,
                'fluentform/validation_user_registration_errors',
                'Use fluentform/validation_user_registration_errors instead of fluentform_validation_user_registration_errors.'
            );

            $errors = apply_filters('fluentform/validation_user_registration_errors', $errors, $formData, $this->form, $fields);
        }

        if ('yes' == Helper::getFormMeta($this->form->id, '_has_user_update') && get_current_user_id()) {
            $errors = apply_filters_deprecated('fluentform_validation_user_update_errors', [
                    $errors,
                    $formData,
                    $this->form,
                    $fields
                ],
                FLUENTFORM_FRAMEWORK_UPGRADE,
                'fluentform/validation_user_update_errors',
                'Use fluentform/validation_user_update_errors instead of fluentform_validation_user_update_errors.'
            );
            $errors = apply_filters('fluentform/validation_user_update_errors', $errors, $formData, $this->form, $fields);
        }

        if ('update' == Arr::get(Helper::getFormMeta($this->form->id, 'postFeeds'), 'post_form_type')) {
            $errors = apply_filters('fluentform/validation_post_update_errors', $errors, $formData, $this->form, $fields);
        }

        if ($errors) {
            // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Exception message, not output
            throw new ValidationException('', 423, null,  ['errors' => $errors]);
        }

        return true;
    }

    protected function validateInput($field, $formData, $form, $fieldName = '', $inputValue = [])
    {
        return Helper::validateInput($field, $formData, $form, $fieldName, $inputValue);
    }

    /**
     * Prevents malicious attacks when the submission
     * count exceeds in an allowed interval.
     * @throws ValidationException
     */
    public function preventMaliciousAttacks()
    {
        $prevent = apply_filters('fluentform/prevent_malicious_attacks', true, $this->form->id);

        if ($prevent) {
            $maxSubmissionCount = apply_filters('fluentform/max_submission_count', 5, $this->form->id);
            $minSubmissionInterval = apply_filters('fluentform/min_submission_interval', 30, $this->form->id);

            $interval = date('Y-m-d H:i:s', strtotime(current_time('mysql')) - $minSubmissionInterval);

            $clientIp = sanitize_text_field($this->app->request->getIp());
            $submissionCount = wpFluent()->table('fluentform_submissions')
                ->where('status', '!=', 'trashed')
                ->where('ip', $clientIp ?: '0.0.0.0')
                ->where('created_at', '>=', $interval)
                ->count();

            if ($submissionCount >= $maxSubmissionCount) {
                throw new ValidationException('', 429, null,  [
                    'errors' => [
                        'restricted' => [
                            // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Sanitized by fluentform_sanitize_html
                            fluentform_sanitize_html(apply_filters(
                                'fluentform/too_many_requests',
                                __('Too Many Requests.', 'fluentform'),
                                $this->form->id
                            )),
                        ],
                    ]
                ]);
            }
        }
    }

    /**
     * Validate form data based on the form restrictions settings.
     *
     * @param $fields
     * @throws ValidationException
     */
    private function validateRestrictions(&$fields)
    {
        $formSettings = FormMeta::retrieve('formSettings', $this->form->id);

        $this->form->settings = is_array($formSettings) ? $formSettings : [];

        $isAllowed = [
            'status'  => true,
            'message' => '',
        ];

        // This will check the following restriction settings.
        // 1. limitNumberOfEntries
        // 2. scheduleForm
        // 3. requireLogin
        // 4. restricted submission based on ip, country and keywords

        /* This filter is deprecated and will be removed soon */
        $isAllowed = apply_filters('fluentform_is_form_renderable', $isAllowed, $this->form);

        $isAllowed = apply_filters('fluentform/is_form_renderable', $isAllowed, $this->form);

        if (!$isAllowed['status']) {
            throw new ValidationException('', 422, null,  [
                'errors' => [
                    'restricted' => [
                        // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Sanitized by fluentform_sanitize_html
                        fluentform_sanitize_html($isAllowed['message']),
                    ],
                ],
            ]);
        }

        // Since we are here, we should now handle if the form should be allowed to submit empty.
        $restrictions = Arr::get($this->form->settings, 'restrictions.denyEmptySubmission', []);

        $this->handleDenyEmptySubmission($restrictions, $fields);

        $formRestrictions = Arr::get($this->form->settings, 'restrictions.restrictForm', []);

        $this->handleRestrictedSubmission($formRestrictions, $fields);
    }

    /**
     * Handle response when empty form submission is not allowed.
     *
     * @param array $settings
     * @param $fields
     * @throws ValidationException
     */
    private function handleDenyEmptySubmission($settings, &$fields)
    {
        // Determine whether empty form submission is allowed or not.
        if (Arr::isTrue($settings, 'enabled')) {
            // confirm this form has no required fields.
            if (!FormFieldsParser::hasRequiredFields($this->form, $fields)) {
                // Filter out the form data which doesn't have values.
                $filteredFormData = array_filter(
                // Filter out the other meta fields that aren't actual inputs.
                    array_intersect_key($this->formData, $fields)
                );
                if (!count(Helper::arrayFilterRecursive($filteredFormData))) {
                    $defaultMessage = esc_html(__('Sorry! You can\'t submit an empty form.','fluentform'));
                    $customMessage = Arr::get($settings, 'message');
                    $customMessage = fluentform_sanitize_html(apply_filters('fluentform/deny_empty_submission_message', $customMessage, $this->form));

                    throw new ValidationException('', 422, null,  [
                        'errors' => [
                            'restricted' => [
                                // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Sanitized by fluentform_sanitize_html
                                !empty($customMessage) ? fluentform_sanitize_html($customMessage) : fluentform_sanitize_html($defaultMessage),
                            ],
                        ],
                    ]);
                }
            }
        }
    }

    /**
     * Handle response when form submission is restricted based on ip, country or keywords.
     *
     * @param array $settings
     * @param $fields
     * @throws ValidationException
     */
    protected function handleRestrictedSubmission($settings, &$fields)
    {
        // Determine this restriction is enabled ot not
        if (!Arr::isTrue($settings, 'enabled')) {
            return;
        }

        $rawIp = $this->app->request->getIp();
        if (is_array($rawIp)) {
            $rawIp = Arr::get($rawIp, '0');
        }
        $ip = sanitize_text_field($rawIp);
        if ($ip) {
            $this->checkIpRestriction($settings, $ip);
        }

        $isCountryRestrictionEnabled = Arr::isTrue($settings, 'fields.country.status');
        if ($isCountryRestrictionEnabled) {
            $country = $this->resolveCountryFromIp($ip);

            if (!$country) {
                $this->handleUnresolvedCountry($settings);
            }

            $this->checkCountryRestriction($settings, $country);
        }

        $this->checkKeyWordRestriction($settings);
    }


    /**
     * Validate nonce.
     * @throws ValidationException
     */
    protected function validateNonce()
    {
        $formId = $this->form->id;
        $shouldVerifyNonce = false;
        /* This filter is deprecated and will be removed soon. */
        $shouldVerifyNonce = $this->app->applyFilters('fluentform_nonce_verify', $shouldVerifyNonce, $formId);

        $shouldVerifyNonce = $this->app->applyFilters('fluentform/nonce_verify', $shouldVerifyNonce, $formId);

        if ($shouldVerifyNonce) {
            $nonce = Arr::get($this->formData, '_fluentform_' . $formId . '_fluentformnonce');
            if (!wp_verify_nonce($nonce, 'fluentform-submit-form')) {
                $errors = apply_filters_deprecated(
                    'fluentForm_nonce_error',
                    [
                        '_fluentformnonce' => [
                            __('Nonce verification failed, please try again.', 'fluentform'),
                        ],
                    ],
                    FLUENTFORM_FRAMEWORK_UPGRADE,
                    'fluentForm/nonce_error',
                    'Use fluentForm/nonce_error instead of fluentForm_nonce_error.'
                );

                $errors = $this->app->applyFilters('fluentForm/nonce_error', $errors);
                // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Exception message, not output
                throw new ValidationException('', 422, null, ['errors' => $errors]);
            }
        }
    }

    /** Validate Akismet Spam
     * @throws ValidationException
     */
    public function handleAkismetSpamError()
    {
        $settings = get_option('_fluentform_global_form_settings');
        if (!$settings || 'validation_failed' != Arr::get($settings, 'misc.akismet_validation')) {
            return;
        }

        $errors = [
            '_fluentformakismet' => __('Submission marked as spammed. Please try again', 'fluentform'),
        ];
        // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Exception message, not output
        throw new ValidationException('', 422, null, ['errors' => $errors]);
    }

    /** Validate CleanTalk Spam
     * @throws ValidationException
     */
    public function handleCleanTalkSpamError()
    {
        $settings = get_option('_fluentform_global_form_settings');
        if (!$settings || 'validation_failed' != Arr::get($settings, 'misc.cleantalk_validation')) {
            return;
        }

        $errors = [
            '_fluentformcleantalk' => __('Submission marked as spammed. Please try again', 'fluentform'),
        ];
        // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Exception message, not output
        throw new ValidationException('', 422, null, ['errors' => $errors]);
    }

    /** Validate CleanTalk Spam While Using API
     * @throws ValidationException
     */
    public function handleCleanTalkSpamErrorUsingAPi()
    {
        $cleantalkSettings = get_option('_fluentform_cleantalk_details');

        if (
            !$cleantalkSettings ||
            'validation_failed' != Arr::get($cleantalkSettings, 'validation')
        ) {
            return;
        }

        $errors = [
            '_fluentformcleantalk' => __('Submission marked as spammed. Please try again', 'fluentform'),
        ];
        // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Exception message, not output
        throw new ValidationException('', 422, null, ['errors' => $errors]);
    }

    public function isAkismetSpam($formData, $form)
    {
         if (!AkismetHandler::isEnabled()) {
            return false;
        }
        $isSpamCheck = apply_filters_deprecated(
            'fluentform_akismet_check_spam',
            [
                true,
                $form->id,
                $formData
            ],
            FLUENTFORM_FRAMEWORK_UPGRADE,
            'fluentform/akismet_check_spam',
            'Use fluentform/akismet_check_spam instead of fluentform_akismet_check_spam.'
        );

        $isSpamCheck = apply_filters('fluentform/akismet_check_spam', $isSpamCheck, $form->id, $formData);

        if (!$isSpamCheck) {
            return false;
        }
        // Let's validate now
        $isSpam = AkismetHandler::isSpamSubmission($formData, $form);

        $isSpam = apply_filters_deprecated(
            'fluentform_akismet_spam_result',
            [
                $isSpam,
                $form->id,
                $formData
            ],
            FLUENTFORM_FRAMEWORK_UPGRADE,
            'fluentform/akismet_spam_result',
            'Use fluentform/akismet_spam_result instead of fluentform_akismet_spam_result.'
        );
        return apply_filters('fluentform/akismet_spam_result', $isSpam, $form->id, $formData);
    }

    public function isCleanTalkSpam($formData, $form)
    {
        if (!CleanTalkHandler::isEnabled()) {
            return false;
        }
        $isSpamCheck = apply_filters('fluentform/cleantalk_check_spam', true, $form->id, $formData);

        if (!$isSpamCheck) {
            return false;
        }
        $isSpam = CleanTalkHandler::isSpamSubmission($formData, $form);

        return apply_filters('fluentform/cleantalk_spam_result', $isSpam, $form->id, $formData);
    }

    public function isCleanTalkSpamUsingApi($formData, $form)
    {
        if (!CleanTalkHandler::isCleantalkActivated()) {
            return false;
        }

        $isSpamCheck = apply_filters('fluentform/cleantalk_check_spam', true, $form->id, $formData);

        if (!$isSpamCheck) {
            return false;
        }

        $isSpam = CleanTalkHandler::spamSubmissionCheckWithApi($formData, $form);

        return apply_filters('fluentform/cleantalk_spam_result', $isSpam, $form->id, $formData);
    }

    /**
     * Validate reCaptcha.
     * Uses 'fluentform/disable_captcha' filter with 'recaptcha' as the captcha type since 6.0.3
     * @throws ValidationException
     */
    private function validateReCaptcha()
    {
        // Check if autoload_captcha is enabled and if it's not recaptcha, skip validation
        if ($this->shouldSkipCaptchaValidation('recaptcha')) {
            return;
        }

        $hasAutoRecap =  apply_filters_deprecated(
            'ff_has_auto_recaptcha',
            [
                false
            ],
            FLUENTFORM_FRAMEWORK_UPGRADE,
            'fluentform/has_recaptcha',
            'Use fluentform/has_recaptcha instead of ff_has_auto_recaptcha.'
        );
        $autoInclude = apply_filters('fluentform/has_recaptcha', $hasAutoRecap);
        $disableReCaptcha = apply_filters('fluentform/disable_captcha', false, $this->form, 'recaptcha');
        
        if (!$disableReCaptcha && (FormFieldsParser::hasElement($this->form, 'recaptcha') || $autoInclude)) {
            $keys = get_option('_fluentform_reCaptcha_details');
            $token = Arr::get($this->formData, 'g-recaptcha-response');
            $version = 'v2_visible';
            if (!empty($keys['api_version'])) {
                $version = $keys['api_version'];
            }
            $isValid = ReCaptcha::validate($token, $keys['secretKey'], $version);
            
            if (!$isValid) {
                // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Exception message, not output
                throw new ValidationException('', 422, null, [
                    'errors' => [
                        'g-recaptcha-response' => [
                            esc_html(__('reCaptcha verification failed, please try again.', 'fluentform')),
                        ],
                    ],
                ]);
            }
        }
    }

    /**
     * Validate hCaptcha.
     *
     * @throws ValidationException
     */
    private function validateHCaptcha()
    {
        // Check if autoload_captcha is enabled and if it's not hcaptcha, skip validation
        if ($this->shouldSkipCaptchaValidation('hcaptcha')) {
            return;
        }
        $hasAutoHcap = apply_filters_deprecated(
            'ff_has_auto_hcaptcha',
            [
                false
            ],
            FLUENTFORM_FRAMEWORK_UPGRADE,
            'fluentform/has_hcaptcha',
            'Use fluentform/has_hcaptcha instead of ff_has_auto_hcaptcha.'
        );
        $autoInclude = apply_filters('fluentform/has_hcaptcha', $hasAutoHcap);
        $disableHCaptcha = apply_filters('fluentform/disable_captcha', false, $this->form, 'hcaptcha');

        FormFieldsParser::resetData();
        if (!$disableHCaptcha && (FormFieldsParser::hasElement($this->form, 'hcaptcha') || $autoInclude)) {
            $keys = get_option('_fluentform_hCaptcha_details');
            $token = Arr::get($this->formData, 'h-captcha-response');
            $isValid = HCaptcha::validate($token, $keys['secretKey']);
            
            if (!$isValid) {
                throw new ValidationException('', 422, null, [
                    'errors' => [
                        'h-captcha-response' => [
                            esc_html(__('hCaptcha verification failed, please try again.', 'fluentform')),
                        ],
                    ],
                ]);
            }
        }
    }

    /**
     * Validate turnstile.
     *
     * @throws ValidationException
     */
    private function validateTurnstile()
    {
        // Check if autoload_captcha is enabled and if it's not turnstile, skip validation
        if ($this->shouldSkipCaptchaValidation('turnstile')) {
            return;
        }

        $hasAutoTurnsTile = apply_filters_deprecated(
            'ff_has_auto_turnstile',
            [
                false
            ],
            FLUENTFORM_FRAMEWORK_UPGRADE,
            'fluentform/has_turnstile',
            'Use fluentform/has_turnstile instead of ff_has_auto_turnstile.'
        );
        $autoInclude = apply_filters('fluentform/has_turnstile', $hasAutoTurnsTile);
        $disableTurnsTile = apply_filters('fluentform/disable_captcha', false, $this->form, 'turnstile');
        
        if (!$disableTurnsTile && (FormFieldsParser::hasElement($this->form, 'turnstile') || $autoInclude)) {
            $keys = get_option('_fluentform_turnstile_details');
            $token = Arr::get($this->formData, 'cf-turnstile-response');
            
            $isValid = Turnstile::validate($token, $keys['secretKey']);
            
            if (!$isValid) {
                throw new ValidationException('', 422, null, [
                    'errors' => [
                        'cf-turnstile-response' => [
                            esc_html(__('Turnstile verification failed, please try again.', 'fluentform')),
                        ],
                    ],
                ]);
            }
        }
    }


    /**
     * Delegate the validation rules & messages to the
     * ones that the validation library recognizes.
     *
     * @param $rules
     * @param $messages
     * @param array $search
     * @param array $replace
     * @return array
     */
    protected function delegateValidations($rules, $messages, $search = [], $replace = [])
    {
        $search = $search ?: ['max_file_size', 'allowed_file_types'];
        $replace = $replace ?: ['max', 'mimes'];

        foreach ($rules as &$rule) {
            $rule = str_replace($search, $replace, $rule);
        }

        foreach ($messages as $key => $message) {
            $newKey = str_replace($search, $replace, $key);
            $messages[$newKey] = $message;
            unset($messages[$key]);
        }

        return [$rules, $messages];
    }

    /**
     * Decide what an unresolved country means for this rule.
     *
     * A block list stays permissive: the providers are third party, and their
     * outage must not stop a site taking submissions. An allow list cannot be
     * honoured at all without a country - letting it through would turn "only
     * these countries" into "anyone" - so it fails closed. Either case can be
     * inverted with the filter.
     *
     * @throws ValidationException
     */
    private function handleUnresolvedCountry($settings)
    {
        // A rule with no countries chosen cannot express an intent, so it must
        // not acquire a brand new way to reject people.
        if (!array_filter((array) Arr::get($settings, 'fields.country.values', []))) {
            return;
        }

        // Derived negatively on purpose: checkCountryRestriction() treats
        // anything that is not fail_on_condition_met as an allow list, and a
        // form saved before validation_type existed has the key absent. Testing
        // for the allow-list string instead would leave those forms enforced as
        // an allow list while being failed open as a block list.
        $isAllowList = 'fail_on_condition_met' !== Arr::get($settings, 'fields.country.validation_type');

        $failClosed = apply_filters(
            'fluentform/country_restriction_fail_closed',
            $isAllowList,
            $this->form,
            $settings
        );

        if (!$failClosed) {
            return;
        }

        $default = __('Sorry! We could not verify your location, so this form cannot be submitted right now.', 'fluentform');

        self::throwValidationException(
            apply_filters('fluentform/country_unresolved_message', $default, $this->form)
        );
    }

    /**
     * Resolve the visitor country, trying each provider in turn.
     *
     * A geo provider can only answer for a routable address; for a private or
     * reserved one ipinfo.io replies {"bogon":true} with no country and apip.cc
     * replies status:fail. resolveIp() yields such an address for CLI and cron
     * submissions, for an unparseable REMOTE_ADDR, and on a site whose reverse
     * proxy sits on a private network. Skipping the lookups there reaches the
     * same answer without two blocking HTTP timeouts.
     *
     * @return string|null
     */
    private function resolveCountryFromIp($ip)
    {
        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return Helper::getCountryCodeFromHeaders(true);
        }

        $cached = self::cachedCountry($ip);

        if (false !== $cached) {
            return 'none' === $cached ? null : $cached;
        }

        $country = null;

        if ($ipInfo = $this->getIpInfo($ip)) {
            $country = self::normalizeCountry(Arr::get($ipInfo, 'country'));
        }

        $answered = null !== $country;

        if (!$country) {
            if (get_transient('fluentform_geo_apip_backoff')) {
                // Nothing was asked, so there is no verdict to remember. Caching
                // here would outlive the back-off and pin the miss indefinitely.
                return Helper::getCountryCodeFromHeaders(true);
            }

            $country = $this->getIpBasedOnCountry($ip, $answered);
        }

        if ($answered) {
            self::cacheCountry($ip, $country);
        }

        return $country;
    }

    /**
     * Providers are third parties; only a real ISO 3166-1 alpha-2 code may
     * reach enforcement or the cache.
     *
     * @param mixed $country
     * @return string|null
     */
    private static function normalizeCountry($country)
    {
        if (!is_string($country)) {
            return null;
        }

        $country = strtoupper(trim($country));

        return preg_match('/^[A-Z]{2}$/', $country) ? $country : null;
    }

    /**
     * @param string $ip
     * @return string|false 'none' for a cached miss, false when not cached
     */
    private static function cachedCountry($ip)
    {
        $shard = get_transient(self::cacheShardKey($ip));

        if (!is_array($shard)) {
            return false;
        }

        $key = md5($ip);

        if (!isset($shard[$key]['c'], $shard[$key]['t'])) {
            return false;
        }

        // Each entry carries its own stamp because the row's TTL is pushed
        // forward by every write, so on a busy form the row never expires.
        if ((time() - (int) $shard[$key]['t']) > self::GEO_CACHE_MINUTES * MINUTE_IN_SECONDS) {
            return false;
        }

        return $shard[$key]['c'];
    }

    /**
     * Sharded so the rows stay small and concurrent submissions rarely collide
     * on the same read-modify-write, and bounded so a flood of unique addresses
     * cannot grow wp_options without limit. Addresses are hashed: this store is
     * not submission data and must not become an IP log.
     *
     * @param string $ip
     * @param string|null $country
     * @return void
     */
    private static function cacheCountry($ip, $country)
    {
        $shardKey = self::cacheShardKey($ip);
        $shard = get_transient($shardKey);
        $shard = is_array($shard) ? $shard : [];

        $key = md5($ip);

        unset($shard[$key]);
        $shard[$key] = ['c' => $country ?: 'none', 't' => time()];

        if (count($shard) > self::GEO_CACHE_SHARD_MAX) {
            $shard = array_slice($shard, -self::GEO_CACHE_SHARD_MAX, null, true);
        }

        set_transient($shardKey, $shard, self::GEO_CACHE_MINUTES * MINUTE_IN_SECONDS);
    }

    /**
     * @param string $ip
     * @return string
     */
    private static function cacheShardKey($ip)
    {
        return 'fluentform_geo_country_' . substr(md5($ip), 0, 2);
    }

    /**
     * Count an inconclusive answer, and park the provider once they repeat.
     *
     * A single timeout or unusable body says nothing about the provider's
     * health for other visitors, so it must not disable enforcement for them;
     * a run of them does.
     *
     * @param string $provider
     * @return void
     */
    private static function recordProviderStrike($provider)
    {
        $key = 'fluentform_geo_' . $provider . '_strikes';
        $strikes = (int) get_transient($key) + 1;

        if ($strikes >= self::GEO_PROVIDER_STRIKES) {
            delete_transient($key);
            self::backOffProvider($provider);

            return;
        }

        set_transient($key, $strikes, self::GEO_BACKOFF_MINUTES * MINUTE_IN_SECONDS);
    }

    /**
     * Whether a status code says the provider is unusable for everyone, rather
     * than just for the address being looked up.
     *
     * Parking a provider is global, so only a provider-wide fault may do it:
     * rejected credentials, exhausted quota, or the provider being down. A
     * per-address oddity must never disable enforcement for other visitors.
     *
     * @param int|string $code
     * @return bool
     */
    private static function isProviderWideFailure($code)
    {
        $code = (int) $code;

        return in_array($code, [401, 403, 429], true) || $code >= 500;
    }

    /**
     * Park a provider that just failed, so it is not re-asked per submission.
     *
     * @param string $provider
     * @return void
     */
    private static function backOffProvider($provider)
    {
        set_transient(
            'fluentform_geo_' . $provider . '_backoff',
            1,
            self::GEO_BACKOFF_MINUTES * MINUTE_IN_SECONDS
        );
    }

    /**
     * Get IP info from ipinfo.io
     *
     * Returns false on any failure - rejected token, outage, malformed body -
     * so the caller falls through to apip.cc and then to the request headers.
     * A misconfigured token is an admin error; it must not cancel every
     * visitor's submission.
     *
     * @return array|false
     */
    private function getIpInfo($ip) {
        $token = Helper::getIpinfo();

        if (!$token || get_transient('fluentform_geo_ipinfo_backoff')) {
            return false;
        }

        // Bearer, not a query parameter: a credential in a URL is logged by
        // every outbound proxy the request passes through.
        $data = wp_remote_get('https://ipinfo.io/' . rawurlencode($ip), [
            'timeout' => self::GEO_TIMEOUT,
            'headers' => ['Authorization' => 'Bearer ' . $token],
        ]);

        if (is_wp_error($data)) {
            self::recordProviderStrike('ipinfo');

            return false;
        }

        $code = wp_remote_retrieve_response_code($data);

        if (200 !== $code) {
            if (self::isProviderWideFailure($code)) {
                self::backOffProvider('ipinfo');
            }

            return false;
        }

        $result = \json_decode(wp_remote_retrieve_body($data), true);

        // Same reasoning as apip.cc below: a body we cannot use is about this
        // address, not the provider's health, so it must not count globally.
        if (!is_array($result)) {
            return false;
        }

        delete_transient('fluentform_geo_ipinfo_strikes');

        return $result;
    }

    /**
     * Get IP and Country from apip.cc, falling back to the request headers.
     *
     * @return string|null
     */
    private function getIpBasedOnCountry($ip, &$answered = false) {
        if (get_transient('fluentform_geo_apip_backoff')) {
            return Helper::getCountryCodeFromHeaders(true);
        }

        $request = wp_remote_get(
            'https://apip.cc/api-json/' . rawurlencode($ip),
            ['timeout' => self::GEO_TIMEOUT]
        );

        if (is_wp_error($request)) {
            self::recordProviderStrike('apip');

            return Helper::getCountryCodeFromHeaders(true);
        }

        $code = wp_remote_retrieve_response_code($request);

        if (200 !== $code) {
            if (self::isProviderWideFailure($code)) {
                self::backOffProvider('apip');
            }

            // FINDING-26: the provider gave us nothing. Return the CDN header only
            // if the site opted into trusting it for enforcement; otherwise null,
            // which hands the decision to handleUnresolvedCountry().
            return Helper::getCountryCodeFromHeaders(true);
        }

        // The provider answered about this address, so the result is a verdict
        // worth remembering even when it is "no country".
        $answered = true;

        $body = \json_decode(wp_remote_retrieve_body($request), true);
        $country = self::normalizeCountry(Arr::get((array) $body, 'CountryCode'));

        if ('success' === Arr::get((array) $body, 'status') && $country) {
            delete_transient('fluentform_geo_apip_strikes');

            return $country;
        }

        // No strike here. A 200 that carries no usable country is an answer about
        // this address, and which address is looked up is chosen by whoever
        // submits - letting it count towards a global park would hand a remote
        // submitter a way to disable the provider for everyone. The miss is
        // cached against this address instead, which is what stops it being
        // re-asked on the next submission.
        return Helper::getCountryCodeFromHeaders(true);
    }

    /**
     * @param $value
     * @param $providedKeywords
     * @return bool
     */
    public static function containsRestrictedKeywords($value, $providedKeywords) {
        $value = (string) $value;
        if ('' === $value) {
            return false;
        }

        foreach ((array) $providedKeywords as $keyword) {
            $keyword = (string) $keyword;
            if ('' === $keyword || self::isUnusableKeyword($keyword)) {
                continue;
            }

            if (preg_match(self::keywordPattern($keyword), $value)) {
                return true;
            }
        }

        return false;
    }

    /**
     * A lone punctuation mark or invisible format character is never a usable
     * restriction keyword.
     *
     * The previous implementation stripped these before matching, so an entry
     * like "." or a stray zero-width space sat in a site's keyword list doing
     * nothing at all. Now that keywords match on the raw value, such an entry
     * would hit almost every submission and silently reject the whole form —
     * and an invisible one (ZWSP, soft hyphen, BOM, picked up by pasting a list
     * from a document) could never be spotted in the settings field. Skipping
     * them protects sites carrying a stray entry without costing anything that
     * ever worked: every character in these two categories was already inert.
     *
     * Deliberately NOT skipped: spaces (\p{Zs}) and tabs/newlines (\p{Cc}) did
     * match under the old tokenizer, so they must keep matching. Currency, math,
     * arrows, emoji and any multi-character keyword ("$$$", "http://") are
     * unaffected — only single characters are considered here.
     *
     * @param string $keyword
     * @return bool
     */
    private static function isUnusableKeyword($keyword)
    {
        return 1 === mb_strlen($keyword, 'UTF-8') && preg_match('/^[\p{P}\p{Cf}]$/u', $keyword);
    }

    /**
     * Build the whole-word matcher for a single restricted keyword.
     *
     * Matching stays whole-word (the keyword glued inside a longer word is not a
     * match), but "word" has to be defined per script rather than by PCRE's \b:
     *
     * - \b/\w never treat combining marks as word characters, not even under
     *   (*UCP). Indic scripts write vowels and the virama as marks, so "বাংলা"
     *   (ব + া + ং + ল + া) has no trailing boundary and could never match.
     *   \p{M} is therefore part of the word class.
     * - Han, Kana, Thai, Lao, Khmer, Myanmar and Tibetan don't separate words at
     *   all, so no boundary can ever exist around a keyword. Whole-word is
     *   meaningless there and the keyword is matched as a substring instead.
     *
     * Everything else — underscore, zero-width joiners, non-ASCII digits — stays
     * a separator, matching the class the previous implementation tokenised on.
     * That keeps this a strict superset of the old matcher: a keyword that used
     * to be blocked is still blocked, and padding a keyword with an invisible
     * ZWNJ can't slip it past the filter.
     *
     * @param string $keyword
     * @return string
     */
    private static function keywordPattern($keyword)
    {
        $quoted = preg_quote($keyword, '/');

        if (preg_match('/[\p{Han}\p{Hiragana}\p{Katakana}\p{Thai}\p{Lao}\p{Khmer}\p{Myanmar}\p{Tibetan}]/u', $keyword)) {
            return '/' . $quoted . '/ui';
        }

        $wordChar = '\p{L}\p{M}\d';

        // Only guard an edge that is itself a word character, so keywords
        // wrapped in punctuation (e.g. "$$$" or "buy!") stay matchable.
        $lead  = preg_match('/^[' . $wordChar . ']/u', $keyword) ? '(?<![' . $wordChar . '])' : '';
        $trail = preg_match('/[' . $wordChar . ']$/u', $keyword) ? '(?![' . $wordChar . '])' : '';

        return '/' . $lead . $quoted . $trail . '/ui';
    }


    /**
     * @throws ValidationException
     */
    private function checkIpRestriction($settings, $ip)
    {
        if (Arr::isTrue($settings, 'fields.ip.status') && $ip) {
            $providedIp = array_map('trim', explode(',', (string) Arr::get($settings, 'fields.ip.values', '')));

            $isFailed = Arr::get($settings, 'fields.ip.validation_type') === 'fail_on_condition_met';

            $failedSubmissionIfExists = $isFailed && in_array($ip, $providedIp);
            $allowSubmissionIfNotExists = !$isFailed && !in_array($ip, $providedIp);

            if ($failedSubmissionIfExists || $allowSubmissionIfNotExists) {
                $defaultMessage = __('Sorry! You can\'t submit a form from your IP address.', 'fluentform');
                $message = apply_filters('fluentform/ip_restriction_message', Arr::get($settings, 'fields.ip.message', $defaultMessage), $this->form);
                self::throwValidationException($message);
            }
        }
    }

    /**
     * @throws ValidationException
     */
    private function checkCountryRestriction($settings, $country)
    {
        if (Arr::isTrue($settings, 'fields.country.status') && $country) {
            $providedCountry = (array) Arr::get($settings, 'fields.country.values', []);

            $isFailed = Arr::get($settings, 'fields.country.validation_type') === 'fail_on_condition_met';

            $failedSubmissionIfExists = $isFailed && in_array($country, $providedCountry);
            $allowSubmissionIfNotExists = !$isFailed && !in_array($country, $providedCountry);

            if ($failedSubmissionIfExists || $allowSubmissionIfNotExists) {
                $defaultMessage = __('Sorry! You can\'t submit this form from the country you are residing.', 'fluentform');
                $message = apply_filters('fluentform/country_restriction_message', Arr::get($settings, 'fields.country.message', $defaultMessage), $this->form);
                self::throwValidationException($message);
            }
        }
    }

    private function checkKeyWordRestriction($settings)
    {
        if (!Arr::isTrue($settings, 'fields.keywords.status')) {
            return;
        }

        $keywords = Arr::get($settings, 'fields.keywords.values');
        if (!$keywords || !is_string($keywords)) {
            return;
        }
        $providedKeywords = explode(',', $keywords);
        $providedKeywords =  array_filter(array_map('trim', $providedKeywords));
        if (!$providedKeywords) {
            return;
        }
        $inputSubmission = array_intersect_key(
            $this->formData,
            array_flip(
                array_keys(
                    FormFieldsParser::getInputs($this->form)
                )
            )
        );
        $defaultMessage = __('Sorry! Your submission contains some restricted keywords.', 'fluentform');
        $message = apply_filters('fluentform/keyword_restriction_message', Arr::get($settings, 'fields.keywords.message', $defaultMessage), $this->form);

        self::checkKeywordsMatching($inputSubmission, $message, $providedKeywords);
    }

    private static function checkKeywordsMatching($inputSubmission, $message, $providedKeywords)
    {
        foreach ($inputSubmission as $value) {
            if (!empty($value)) {
                if (is_array($value)) {
                    self::checkKeywordsMatching($value, $message, $providedKeywords);
                } else {
                    if (self::containsRestrictedKeywords($value, $providedKeywords)) {
                        self::throwValidationException($message);
                    }
                }
            }
        }
    }

    /**
     * @throws ValidationException
     */
    public static function throwValidationException($message) {
        throw new ValidationException('', 422, null,  [
            'errors' => [
                'restricted' => [
                    // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Sanitized by fluentform_sanitize_html
                    fluentform_sanitize_html($message)
                ],
            ],
        ]);
    }

    /**
     * Check if captcha validation should be skipped based on autoload captcha settings
     *
     * When autoload captcha is enabled, only the selected captcha type should be validated.
     * This method returns true if the current captcha type is NOT the selected autoload type,
     * preventing unnecessary validation of multiple captcha types on the same form.
     *
     * @param string $captchaType The captcha type to check ('recaptcha', 'hcaptcha', 'turnstile')
     * @return bool True if validation should be skipped, false otherwise
     */
    private function shouldSkipCaptchaValidation($captchaType)
    {
        $globalSettings = get_option('_fluentform_global_form_settings');
        $autoloadEnabled = Arr::get($globalSettings, 'misc.autoload_captcha');

        // If autoload captcha is not enabled, don't skip any validation
        if (!$autoloadEnabled) {
            return false;
        }

        $selectedCaptchaType = Arr::get($globalSettings, 'misc.captcha_type');

        // If the current captcha type matches the selected autoload type, proceed with validation
        if ($captchaType === $selectedCaptchaType) {
            return false;
        }

        return true; // Skip validation for non-selected captcha types
    }
}
