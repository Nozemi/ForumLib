<?php
    namespace SBLib\TemplatingEngine\Components;

    use SBLib\TemplatingEngine\AbstractComponent;

    class Head extends AbstractComponent {

        protected function getInitializer() {
            return __CLASS__;
        }
    }