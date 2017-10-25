<?php
    namespace SBLib\TemplatingEngine\Elements;

    use SBLib\TemplatingEngine\AbstractElement;

    class Header extends AbstractElement {

        protected function getInitializer() {
            return __CLASS__;
        }
    }