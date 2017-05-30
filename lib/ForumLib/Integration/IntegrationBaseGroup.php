<?php
    namespace ForumLib\Integration;

    use ForumLib\Users\Group;

    abstract class IntegrationBaseGroup extends IntegrationBase {
        abstract public function getGroups(Group $group);
        abstract public function getGroup($id, Group $group);
    }