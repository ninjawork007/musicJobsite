<?php

namespace Vocalizr\AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class Waveform
 *
 * @package Vocalizr\AppBundle\Entity
 *
 * @ORM\Table(name="waveforms")
 * @ORM\Entity()
 */
class Waveform
{
    /**
     * @var string
     *
     * @ORM\Id()
     * @ORM\Column(name="file_path", type="string")
     */
    private $path;

    /**
     * @var float[]
     *
     * @ORM\Column(name="peaks", type="simple_array")
     */
    private $peaks;

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @param string $path
     *
     * @return Waveform
     */
    public function setPath($path)
    {
        $this->path = $path;
        return $this;
    }

    /**
     * @return float[]
     */
    public function getPeaks()
    {
        return $this->peaks;
    }

    /**
     * @param float[] $peaks
     *
     * @return Waveform
     */
    public function setPeaks($peaks)
    {
        $this->peaks = $peaks;
        return $this;
    }
}