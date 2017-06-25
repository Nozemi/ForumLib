<?php
    namespace ForumLib\Integration\Nozum;

    use ForumLib\Database\DBUtilQuery;
    use ForumLib\Integration\IntegrationBaseGroup;
    use ForumLib\Users\Group;
    use \PDO;

    class NozumGroup extends IntegrationBaseGroup {

        public function getGroups(Group $group) {
            $getGroups = new DBUtilQuery;
            $getGroups->setName('getGroups')
                ->setQuery("SELECT * FROM `{{DBP}}groups`")
                ->setDBUtil($this->S)
                ->execute();

            $tmpGroups = $this->S->getResultByName($getGroups->getName());

            $groups = array();
                foreach($tmpGroups as $group) {
                $gR = new Group($this->S);
                $groups[] = $gR->getGroup($group['id']);
            }

            return $groups;
        }

        public function getGroup($id, Group $group) {
            $getGroup = new DBUtilQuery;
            $getGroup->setName('getGroup')
                ->setQuery("SELECT * FROM `{{DBP}}groups` WHERE `id` = :id")
                ->addParameter('id', $id, PDO::PARAM_INT)
                ->setDBUtil($this->S)
                ->execute();

            if(empty($gR)) {
                $this->lastError[] = 'Failed to get group.';
                return false;
            }

            $gR = $this->S->getResultByName($getGroup->getName());

            $group = new Group($this->S);
            $group->setId($gR['id'])
                ->setDescription($gR['desc'])
                ->setName($gR['title'])
                ->setAdmin($gR['admin'])
                ->unsetSQL();

            $this->lastMessage[] = 'Successfully loaded group.';
            return $group;
        }
    }