<?php

if (!interface_exists("AuthService")) {

  interface AuthService {
	  public function canLogin();
	  public function Login($id);
	  public function Link($id);
    public function Unlink($id);
	  public function getButtons();

    public function getButtonsLinked($id);

    public function install();

  }
}
?>
