<?php
    namespace ForumLib\Integration\Nozum;

    use ForumLib\Integration\IntegrationBasePermissions;
    use ForumLib\Users\Permissions;
    use ForumLib\Users\User;

    class NozumPermissions extends IntegrationBasePermissions {

        public function checkPermissions(User $user, $object = null, Permissions $permissions) {
            if(is_null($object)) $object = $permissions->OI;
            $query = 'No query.';

            if($object instanceof Category) {

            }

            if($object instanceof Topic) {
                $query = "SELECT * FROM `{{DBP}}permissions` WHERE `topicId` = :id AND `groupId` = :gid";
            }

            if($object instanceof Thread) {

            }

            if($object instanceof Post) {

            }

            $this->S->prepareQuery($this->S->replacePrefix('{{DBP}}', $query));
            if($this->S->executeQuery(array(':id' => $object->id, ':gid' => $user->groupId))) {
                return $this->S->fetch();
            } else {
                return $this->S->getLastError();
            }
        }

        public function getPermissions($id = null, Permissions $permissions) {
            if(is_null($permissions->id) && !is_null($id)) {
                $id = $permissions->id;
            } else {
                $this->lastError[] = 'Something went wrong while getting permissions. [1]';
                return false;
            }

            /*
            We'll need to know where to get the permissions from, wheter it's a category, topic or thread.
            To do this, we have the method getType() in those three objects, to tell us what the object is.
            */
            switch($permissions->OI->getType()) {
                case 'ForumLib\Forums\Thread':
                    $permissions->type = 2;
                    $query = "SELECT * FROM `{{DBP}}permissions` WHERE `threadId` = :id";
                    break;
                case 'ForumLib\Forums\Topic':
                    $permissions->type = 1;
                    $query = "SELECT * FROM `{{DBP}}permissions` WHERE `topicId` = :id";
                    break;
                case 'ForumLib\Forums\Category':
                default:
                    $permissions->type = 0;
                    $query = "SELECT * FROM `{{DBP}}permissions` WHERE `categoryId` = :id";
                    break;
            }

            $this->S->prepareQuery($this->S->replacePrefix('{{DBP}}', $query));
            if($this->S->executeQuery(array(
                ':id' => $this->id
            ))) {
                $perms = $this->S->fetchAll(); // Let's get the query results.

                $users = array(); $groups = array();

                for($i = 0; $i < count($perms); $i++) {
                    $P = new permissions($this->S);

                    if(is_null($perms['userId'])) {
                        $P->setUserId(null)
                            ->setGroupId($perms[$i]['groupId']);
                    } else {
                        $P->setGroupId(null)
                            ->setUserId($perms[$i]['userId']);
                    }

                    $P->setPost($perms[$i]['post'])
                        ->setRead($perms[$i]['read'])
                        ->setMod($perms[$i]['mod'])
                        ->setAdmin($perms[$i]['admin']);

                    if(is_null($P->getUserId)) {
                        $groups[] = $P;
                    } else {
                        $users[] = $P;
                    }
                }

                $this->lastMessage[] = 'Successfully loaded permissions.';

                return array(
                    'users'   => $users,
                    'groups'  => $groups
                );
            } else {
                if(defined('DEBUG')) {
                    $this->lastError[] = $this->S->getLastError();
                } else {
                    $this->lastError[] = 'Something went wrong while getting permissions. [2]';
                }
                return false;
            }
        }
    }