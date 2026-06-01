<?php

if (!defined('ABSPATH')) exit;


use MailPoetVendor\Twig\Environment;
use MailPoetVendor\Twig\Error\LoaderError;
use MailPoetVendor\Twig\Error\RuntimeError;
use MailPoetVendor\Twig\Extension\CoreExtension;
use MailPoetVendor\Twig\Extension\SandboxExtension;
use MailPoetVendor\Twig\Markup;
use MailPoetVendor\Twig\Sandbox\SecurityError;
use MailPoetVendor\Twig\Sandbox\SecurityNotAllowedTagError;
use MailPoetVendor\Twig\Sandbox\SecurityNotAllowedFilterError;
use MailPoetVendor\Twig\Sandbox\SecurityNotAllowedFunctionError;
use MailPoetVendor\Twig\Source;
use MailPoetVendor\Twig\Template;

/* emails/statsNotification.txt */
class __TwigTemplate_73223e600e7d98691234a281f9573e6bebabbe006922fa4a10de4b25ee450391 extends Template
{
    private $source;
    private $macros = [];

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->blocks = [
            'content' => [$this, 'block_content'],
        ];
    }

    protected function doGetParent(array $context)
    {
        // line 1
        return "emails/statsNotificationLayout.txt";
    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        $macros = $this->macros;
        $this->parent = $this->loadTemplate("emails/statsNotificationLayout.txt", "emails/statsNotification.txt", 1);
        yield from $this->parent->unwrap()->yield($context, array_merge($this->blocks, $blocks));
    }

    // line 3
    public function block_content($context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 4
        if ($this->extensions['MailPoet\Twig\Functions']->isGarden()) {
            // line 5
            yield $this->env->getRuntime('MailPoetVendor\Twig\Runtime\EscaperRuntime')->escape(($context["blogName"] ?? null), "html", null, true);
            yield "

";
            // line 7
            yield $this->extensions['MailPoet\Twig\I18n']->translate("Your campaign stats");
            yield "

";
            // line 9
            if (($context["recipientFirstName"] ?? null)) {
                yield $this->env->getRuntime('MailPoetVendor\Twig\Runtime\EscaperRuntime')->escape(MailPoetVendor\Twig\Extension\CoreExtension::replace($this->extensions['MailPoet\Twig\I18n']->translate("Hi %s,"), ["%s" => ($context["recipientFirstName"] ?? null)]), "html", null, true);
                yield "

";
            }
            // line 12
            yield $this->env->getRuntime('MailPoetVendor\Twig\Runtime\EscaperRuntime')->escape(MailPoetVendor\Twig\Extension\CoreExtension::replace($this->extensions['MailPoet\Twig\I18n']->translate("Here's how your campaign \"%s\" performed in the first 24 hours."), ["%s" => ($context["subject"] ?? null)]), "html", null, true);
            yield "

";
            // line 14
            yield $this->extensions['MailPoet\Twig\I18n']->translate("Clicked");
            yield ": ";
            yield $this->extensions['MailPoet\Twig\Functions']->statsNumberFormatI18n(($context["clicked"] ?? null));
            yield "% (";
            yield $this->extensions['MailPoet\Twig\Functions']->clickedStatsTextGarden(($context["clicked"] ?? null));
            yield ")
";
            // line 15
            yield $this->extensions['MailPoet\Twig\I18n']->translate("Opened");
            yield ": ";
            yield $this->extensions['MailPoet\Twig\Functions']->statsNumberFormatI18n(($context["opened"] ?? null));
            yield "%
";
            // line 16
            yield $this->extensions['MailPoet\Twig\I18n']->translate("Machine-opened");
            yield ": ";
            yield $this->extensions['MailPoet\Twig\Functions']->statsNumberFormatI18n(($context["machineOpened"] ?? null));
            yield "%
";
            // line 17
            yield $this->extensions['MailPoet\Twig\I18n']->translate("Unsubscribed");
            yield ": ";
            yield $this->extensions['MailPoet\Twig\Functions']->statsNumberFormatI18n(($context["unsubscribed"] ?? null));
            yield "%
";
            // line 18
            yield $this->extensions['MailPoet\Twig\I18n']->translate("Bounced");
            yield ": ";
            yield $this->extensions['MailPoet\Twig\Functions']->statsNumberFormatI18n(($context["bounced"] ?? null));
            yield "%

";
            // line 20
            yield $this->extensions['MailPoet\Twig\I18n']->translate("View full campaign report");
            yield "
  ";
            // line 21
            yield ($context["linkStats"] ?? null);
            yield "
";
        } else {
            // line 23
            yield $this->extensions['MailPoet\Twig\I18n']->translate("Your stats are in!");
            yield "

";
            // line 25
            yield $this->env->getRuntime('MailPoetVendor\Twig\Runtime\EscaperRuntime')->escape(($context["subject"] ?? null), "html", null, true);
            yield "

";
            // line 27
            if (($context["subscribersLimitReached"] ?? null)) {
                // line 28
                yield $this->env->getRuntime('MailPoetVendor\Twig\Runtime\EscaperRuntime')->escape(MailPoetVendor\Twig\Extension\CoreExtension::replace($this->extensions['MailPoet\Twig\I18n']->translate("Congratulations, you now have more than [subscribersLimit] subscribers!"), ["[subscribersLimit]" => ($context["subscribersLimit"] ?? null)]), "html", null, true);
                yield "

";
                // line 30
                if (($context["hasValidApiKey"] ?? null)) {
                    // line 31
                    yield $this->env->getRuntime('MailPoetVendor\Twig\Runtime\EscaperRuntime')->escape(MailPoetVendor\Twig\Extension\CoreExtension::replace($this->extensions['MailPoet\Twig\I18n']->translate("Your plan is limited to [subscribersLimit] subscribers."), ["[subscribersLimit]" => ($context["subscribersLimit"] ?? null)]), "html", null, true);
                    yield "
";
                } else {
                    // line 33
                    yield $this->env->getRuntime('MailPoetVendor\Twig\Runtime\EscaperRuntime')->escape(MailPoetVendor\Twig\Extension\CoreExtension::replace($this->extensions['MailPoet\Twig\I18n']->translate("Our free version is limited to [subscribersLimit] subscribers."), ["[subscribersLimit]" => ($context["subscribersLimit"] ?? null)]), "html", null, true);
                    yield "
";
                }
                // line 35
                yield $this->extensions['MailPoet\Twig\I18n']->translate("You need to upgrade now to be able to continue using MailPoet.");
                yield "

";
                // line 37
                yield $this->extensions['MailPoet\Twig\I18n']->translate("Upgrade Now");
                yield "
  ";
                // line 38
                yield ($context["upgradeNowLink"] ?? null);
                yield "
";
            }
            // line 40
            yield "
";
            // line 41
            yield $this->extensions['MailPoet\Twig\Functions']->statsNumberFormatI18n(($context["clicked"] ?? null));
            yield "% ";
            yield $this->extensions['MailPoet\Twig\I18n']->translate("clicked");
            yield "
  ";
            // line 42
            yield $this->extensions['MailPoet\Twig\Functions']->clickedStatsText(($context["clicked"] ?? null));
            yield "

";
            // line 44
            yield $this->extensions['MailPoet\Twig\Functions']->statsNumberFormatI18n(($context["opened"] ?? null));
            yield "% ";
            yield $this->extensions['MailPoet\Twig\I18n']->translate("opened");
            yield "

";
            // line 46
            yield $this->extensions['MailPoet\Twig\Functions']->statsNumberFormatI18n(($context["machineOpened"] ?? null));
            yield "% ";
            yield $this->extensions['MailPoet\Twig\I18n']->translate("machine-opened");
            yield "

";
            // line 48
            yield $this->extensions['MailPoet\Twig\Functions']->statsNumberFormatI18n(($context["unsubscribed"] ?? null));
            yield "% ";
            yield $this->extensions['MailPoet\Twig\I18n']->translate("unsubscribed");
            yield "

";
            // line 50
            yield $this->extensions['MailPoet\Twig\Functions']->statsNumberFormatI18n(($context["bounced"] ?? null));
            yield "% ";
            yield $this->extensions['MailPoet\Twig\I18n']->translate("bounced");
            yield "

";
            // line 52
            if ((($context["topLinkClicks"] ?? null) > 0)) {
                // line 53
                yield $this->extensions['MailPoet\Twig\I18n']->translate("Most clicked link");
                yield "
  ";
                // line 54
                yield $this->env->getRuntime('MailPoetVendor\Twig\Runtime\EscaperRuntime')->escape(($context["topLink"] ?? null), "html", null, true);
                yield "

  ";
                // line 56
                yield $this->env->getRuntime('MailPoetVendor\Twig\Runtime\EscaperRuntime')->escape(MailPoetVendor\Twig\Extension\CoreExtension::replace($this->extensions['MailPoet\Twig\I18n']->translate("%s unique clicks"), ["%s" => ($context["topLinkClicks"] ?? null)]), "html", null, true);
                yield "
";
            }
            // line 58
            yield "
";
            // line 59
            yield $this->extensions['MailPoet\Twig\I18n']->translate("View all stats");
            yield "
  ";
            // line 60
            yield ($context["linkStats"] ?? null);
            yield "
";
        }
        return; yield '';
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName()
    {
        return "emails/statsNotification.txt";
    }

    /**
     * @codeCoverageIgnore
     */
    public function isTraitable()
    {
        return false;
    }

    /**
     * @codeCoverageIgnore
     */
    public function getDebugInfo()
    {
        return array (  225 => 60,  221 => 59,  218 => 58,  213 => 56,  208 => 54,  204 => 53,  202 => 52,  195 => 50,  188 => 48,  181 => 46,  174 => 44,  169 => 42,  163 => 41,  160 => 40,  155 => 38,  151 => 37,  146 => 35,  141 => 33,  136 => 31,  134 => 30,  129 => 28,  127 => 27,  122 => 25,  117 => 23,  112 => 21,  108 => 20,  101 => 18,  95 => 17,  89 => 16,  83 => 15,  75 => 14,  70 => 12,  63 => 9,  58 => 7,  53 => 5,  51 => 4,  47 => 3,  36 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("", "emails/statsNotification.txt", "/home/circleci/mailpoet/mailpoet/views/emails/statsNotification.txt");
    }
}
