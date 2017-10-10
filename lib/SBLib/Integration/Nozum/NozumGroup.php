<?php
    namespace SBLib\Integration\Nozum;

    use SBLib\Database\DBUtilQuery;
    use SBLib\Integration\IntegrationBaseGroup;
    use SBLib\Users\Group;
    use \PDO;

    class NozumGroup extends IntegrationBaseGroup {

        public function getGroups(Group $group) {
            $getGroups = new DBUtilQuery;
            $getGroups->setName('getGroups')
                ->setMultipleRows(true)
                ->setQuery("SELECT * FROM `{{DBP}}groups`")
                ->setDBUtil($this->S)
                ->execute();

            $tmpGroups = $getGroups->result();

            $groups = array();
            foreach($tmpGroups as $group) {
                $gR = new Group($this->S);
                $groups[] = $gR->setId($group['id'])
                                ->setName($group['title'])
                                ->setAdmin($group['admin'])
                                ->setDescription($group['desc']);
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

            return $group;
        }
    }