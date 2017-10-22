<?php
    namespace SBLib\Handlers\Pages;

    use SBLib\Handlers\AbstractPage;

    class ErrorsPage extends AbstractPage {
        protected $_validParams = ['errorCode'];

        public function __construct($params, $page) {
            parent::__construct($params);

            echo "Something went wrong while getting the page: {$page} | Most likely case is that there is no such class as ForumPage.<hr>";
        }
    }