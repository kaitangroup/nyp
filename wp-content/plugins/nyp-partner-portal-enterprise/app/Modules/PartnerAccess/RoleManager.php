<?php

namespace NYP\Modules\PartnerAccess;

class RoleManager
{
    const ROLE = 'nyp_partner';

    public function register()
    {
        if (!get_role(self::ROLE)) {

            add_role(
                self::ROLE,
                'NYP Partner',
                [
                    'read' => true,
                ]
            );
        }
    }
}