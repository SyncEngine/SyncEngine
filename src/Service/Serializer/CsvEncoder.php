<?php

namespace SyncEngine\Service\Serializer;

use SyncEngine\Service\Interface\CodecInterface;

class CsvEncoder extends \Symfony\Component\Serializer\Encoder\CsvEncoder implements CodecInterface
{

}
