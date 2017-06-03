<?php
    namespace ForumLib\Integration\Nozum;

    use ForumLib\Integration\IntegrationBaseGroup;
    use ForumLib\Users\Group;

    class NozumGroup extends IntegrationBaseGroup {

        public function getGroups(Group $group) {
            $this->S->prepareQuery($this->S->replacePrefix('{{DBP}}', "
                SELECT * FROM `{{DBP}}groups`
            "));

            if($this->S->executeQuery()) {
                $gRps = $this->S->fetchAll();

                $groups = array();
                    foreach($gRps as $group) {
                    $gR = new Group($this->S);
                    $groups[] = $gR->getGroup($group['id']);
                }

                return $groups;
            } else {
                if(defined('DEBUG')) {
                    $this->lastError[] = $this->S->getLastError();
                } else {
                    $this->lastError[] = 'Something went wrong while getting groups.';
                }
                return false;
            }
        }

        public function getGroup($id, Group $group) {
            $this->S->prepareQuery($this->S->replacePrefix('{{DBP}}', "
              SELECT * FROM `{{DBP}}groups` WHERE `id` = :id
            "));
            if($this->S->executeQuery(array(
                ':id' => $id
            ))) {
                $gR = $this->S->fetch();

                if(empty($gR)) {
                    $this->lastError[] = 'Failed to get group.';
                    return false;
                }

                $group = new Group($this->S);
                $group->setId($gR['id'])
                    ->setDescription($gR['desc'])
                    ->setName($gR['title'])
                    ->setAdmin($gR['admin'])
                    ->unsetSQL();

                $this->lastMessage[] = 'Successfully loaded group.';
                return $group;
            } else {
                if(defined('DEBUG')) {
                    $this->lastError[] = $this->S->getLastError();
                } else {
                    $this->lastError[] = 'Something went wrong while getting group.';
                }
                return false;
            }
        }
    }