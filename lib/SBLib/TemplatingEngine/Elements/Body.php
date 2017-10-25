<?php
    namespace SBLib\TemplatingEngine\Elements;

    use SBLib\TemplatingEngine\AbstractElement;

    class Body extends AbstractElement {

        function getInitializer() {
            return __CLASS__;
        }
    }