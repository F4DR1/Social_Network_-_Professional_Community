<?php
    enum Auth {
        case Login;
        case Register;

        public function text(): string
        {
            return match($this) 
            {
                Auth::Login => 'login',
                Auth::Register => 'register',
            };
        }
    }
?>