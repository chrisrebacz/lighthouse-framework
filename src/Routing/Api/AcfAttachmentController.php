<?php
namespace Lighthouse\Routing\Api;

class AcfAttachmentController extends AcfController
{
    public function register_hooks() {
        $this->type = 'attachment';
        parent::register_hooks();
    }
}