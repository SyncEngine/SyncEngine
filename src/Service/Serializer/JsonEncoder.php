<?php

namespace SyncEngine\Service\Serializer;

use SyncEngine\Service\Interface\CodecInterface;

class JsonEncoder extends \Symfony\Component\Serializer\Encoder\JsonEncoder implements CodecInterface
{

}
