<?php
    namespace ForumLib\Integration;


    class MyBB {
        private $SQL;

        /**
         * MyBB constructor.
         *
         * Send the PSQL object with the database information to the MyBB forum.
         *
         * @param PSQL $_SQL
         */
        public function __construct(PSQL $_SQL) {
            $this->SQL = $_SQL;
        }
    }