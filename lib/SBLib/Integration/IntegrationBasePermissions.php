<?php
    namespace SBLib\Integration;

    use SBLib\Users\Permissions;
    use SBLib\Users\User;

    abstract class IntegrationBasePermissions extends IntegrationBase {
        abstract public function checkPermissions(User $user, $object = null, Permissions $permissions);
        abstract public function getPermissions($id = null, Permissions $permissions);
    }