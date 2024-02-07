<?php

namespace SyncEngine\Service\Interface;

use Symfony\Component\Serializer\Encoder\DecoderInterface;
use Symfony\Component\Serializer\Encoder\EncoderInterface;

interface CodecInterface extends EncoderInterface, DecoderInterface
{
}
