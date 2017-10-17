<?php
    namespace SBLib\Integration;

    use SBLib\Users\Group;

    abstract class IntegrationBaseGroup extends IntegrationBase {
        abstract public function getGroups(Group $group);
        abstract public function getGroup($id, Group $group);
        abstract public function createGroup(Group $group);
    }