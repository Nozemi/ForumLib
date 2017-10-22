<?php
    namespace SBLib\Database\Tables;

    use SBLib\Database\AbstractTable;

    use SBLib\Database\Elements\Integer;
    use SBLib\Database\Elements\Varchar;
    use SBLib\Database\Elements\Datetime;
    use SBLib\Database\Elements\LongText;

    class UsersTable extends AbstractTable {

        public function __construct() {
            $this->_columns = [
                new Integer('id', 10, false, true, true, true, true),
                new Varchar('username', 25, false, false, false, true, true),
                new Varchar('password', 255, false, false, false, false, true),
                new Varchar('email', 120, false, false, false, true, true),
                new Varchar('avatar', 255, 'defaultAvatar.png'),
                new Integer('group', 3, false, false, false, false, true),
                new Varchar('regip', 255),
                new Varchar('lastip', 255),
                new Datetime('regDate'),
                new Datetime('lastActive'),
                new LongText('about'),
                new Varchar('location', 50),
                new Varchar('discordId', 255)
            ];
        }
    }