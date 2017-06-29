<?php
    namespace ForumLib\Integration\Nozum;

    use ForumLib\Database\DBUtilQuery;
    use ForumLib\Integration\IntegrationBasePermissions;

    use ForumLib\Users\Permissions;
    use ForumLib\Users\User;

    use ForumLib\Forums\Category;
    use ForumLib\Forums\Topic;
    use ForumLib\Forums\Thread;
    use ForumLib\Forums\Post;

    use \PDO;

    class NozumPermissions extends IntegrationBasePermissions {

        public function checkPermissions(User $user, $object = null, Permissions $permissions) {
            if(is_null($object)) $object = $permissions->OI;

            $checkPermissions = new DBUtilQuery;
            $checkPermissions->setName('checkPermissions')
                ->setDBUtil($this->S);

            if($object instanceof Category) {

            }

            if($object instanceof Topic) {
                $checkPermissions->setQuery("SELECT * FROM `{{DBP}}permissions` WHERE `topicId` = :id AND `groupId` = :gid");
            }

            if($object instanceof Thread) {

            }

            if($object instanceof Post) {

            }

            $checkPermissions->addParameter(':id', $object->id, PDO::PARAM_INT)
                ->addParameter(':gid', $user->groupId, PDO::PARAM_INT)
                ->execute();

            return $this->S->getResultByName($checkPermissions->getName());
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

            $getPermissions = new DBUtilQuery;
            $getPermissions->setName('getPermissions')
                ->setQuery($query)
                ->addParameter(':id', $id, PDO::PARAM_INT)
                ->setDBUtil($this->S)
                ->execute();

            $users = array(); $groups = array();

            $perms = $this->S->getResultByName($getPermissions->getName());

            for($i = 0; $i < count($perms); $i++) {
                $P = new Permissions($this->S);

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

                if(is_null($P->getUserId())) {
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
        }
    }