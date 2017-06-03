<?php
    namespace ForumLib\Integration\vB3;

    use ForumLib\Integration\IntegrationBasePermissions;
    use ForumLib\Users\Permissions;
    use ForumLib\Users\User;

    class vB3Permissions extends IntegrationBasePermissions {

        public function checkPermissions(User $user, $object = null, Permissions $permissions) {
            // TODO: Implement checkPermissions() method.
        }

        public function getPermissions($id = null, Permissions $permissions) {
            // TODO: Implement getPermissions() method.
        }
    }