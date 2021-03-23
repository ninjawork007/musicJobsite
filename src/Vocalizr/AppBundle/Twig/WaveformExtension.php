<?php

namespace Vocalizr\AppBundle\Twig;

use Symfony\Component\Routing\RouterInterface;
use Vocalizr\AppBundle\Service\WaveformService;

/**
 * Class WaveformExtension
 *
 * @package Vocalizr\AppBundle\Twig
 */
class WaveformExtension extends \Twig_Extension
{
    private $waveformService;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * WaveformExtension constructor.
     *
     * @param WaveformService $waveformService
     * @param RouterInterface $router
     */
    public function __construct(WaveformService $waveformService, RouterInterface $router)
    {
        $this->waveformService = $waveformService;
        $this->router          = $router;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'waveform_extension';
    }

    /**
     * Returns a list of filters to add to the existing list.
     *
     * @return array An array of filters
     */
    public function getFilters()
    {
        return [
            'waveform_json' => new \Twig_Filter_Method($this, 'getWaveformJson'),
        ];
    }

    /**
     * @param mixed|object $audio
     *
     * @return string
     */
    public function getWaveformJson($audio)
    {
        $data = [
            'mode'  => 'deferred',
            'peaks' => [],
        ];
        $waveform = null;

        if (is_array($audio)) {
            if (isset($audio['id']) && isset($audio['type'])) {
                $audio = $this->waveformService->findAudioByDeferredData($audio['type'], $audio['id']);
            } else {
                $audio = null;
            }
        }

        $waveform = $this->waveformService->findWaveform($audio);

        if ($waveform) {
            $data['peaks'] = $waveform->getPeaks();
            $data['mode']  = 'ondemand';
        } else {
            $data['url'] = $this->router->generate('audio_waveform', $this->waveformService->getDeferredData($audio));
        }

        return json_encode($data);
    }
}