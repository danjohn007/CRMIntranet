<?php
require_once ROOT_PATH . '/app/controllers/BaseController.php';

class LandingController extends BaseController {
    public function index() {
        $this->view('landing/index', [
            'title' => 'Inicio - ' . getSiteName(),
        ]);
    }
}
