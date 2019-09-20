<?php
namespace PayetConsulting\JWT;

use PayetConsulting\JWT\Application\JsonWebTokenTrait;
use Silex\Application;

class JsonWebTokenApplication extends Application
{
    use JsonWebTokenTrait;
}
