<?php

namespace NYP\Modules\PartnerAccess;

class ApprovalManager
{
    public function register()
    {

       
        add_action(
            'show_user_profile',
            [$this, 'partner_status_field']
        );

        add_action(
            'edit_user_profile',
            [$this, 'partner_status_field']
        );

        add_action(
            'personal_options_update',
            [$this, 'save_partner_status']
        );

        add_action(
            'edit_user_profile_update',
            [$this, 'save_partner_status']
        );

        
    }

    public function partner_status_field($user)
    {
       

   

        if (!in_array('nyp_partner', (array) $user->roles, true)) {
            return;
        }

        $status = get_user_meta(
            $user->ID,
            'nyp_partner_status',
            true
        );

        ?>

        <h2>NYP Partner Settings</h2>

        <table class="form-table">
            <tr>
                <th>
                    <label for="nyp_partner_status">
                        Partner Status
                    </label>
                </th>

                <td>
                    <select
                        name="nyp_partner_status"
                        id="nyp_partner_status"
                    >

                        <option value="pending"
                            <?php selected($status, 'pending'); ?>>
                            Pending
                        </option>

                        <option value="approved"
                            <?php selected($status, 'approved'); ?>>
                            Approved
                        </option>

                        <option value="rejected"
                            <?php selected($status, 'rejected'); ?>>
                            Rejected
                        </option>

                        <option value="suspended"
                            <?php selected($status, 'suspended'); ?>>
                            Suspended
                        </option>

                    </select>
                </td>
            </tr>
        </table>

        <?php
    }

    public function save_partner_status($user_id)
    {
        if (!current_user_can('edit_user', $user_id)) {
            return;
        }

        if (!isset($_POST['nyp_partner_status'])) {
            return;
        }

        update_user_meta(
            $user_id,
            'nyp_partner_status',
            sanitize_text_field(
                wp_unslash($_POST['nyp_partner_status'])
            )
        );
    }
}