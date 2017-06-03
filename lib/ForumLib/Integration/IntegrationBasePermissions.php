<?php
    namespace ForumLib\Integration;

    use ForumLib\Users\Permissions;
    use ForumLib\Users\User;

    abstract class IntegrationBasePermissions extends IntegrationBase {
        abstract public function checkPermissions(User $user, $object = null, Permissions $permissions);
        abstract public function getPermissions($id = null, Permissions $permissions);
    }