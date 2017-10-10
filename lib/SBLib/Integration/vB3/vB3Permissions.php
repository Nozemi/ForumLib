<?php
    namespace SBLib\Integration\vB3;

    use SBLib\Integration\IntegrationBasePermissions;
    use SBLib\Users\Permissions;
    use SBLib\Users\User;

    class vB3Permissions extends IntegrationBasePermissions {

        public function checkPermissions(User $user, $object = null, Permissions $permissions) {
            // TODO: Implement checkPermissions() method.
        }

        public function getPermissions($id = null, Permissions $permissions) {
            // TODO: Implement getPermissions() method.
        }
    }