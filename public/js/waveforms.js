function Waveforms () {
    Waveforms.players = [];
    this.currentPlayer = null;
    this.init = function () {
        this.initializeWraps();
        $(document).ajaxSuccess(() => {
            setTimeout(() => {
                this.initializeWraps();
            }, 500);
        });
        $(window).on('waveform-redraw', this.redraw);
    };

    this.initializeWraps = function () {
        var $waveformWraps = $('.js-waveform-wrap:not(.initialized)');

        var self = this;

        $waveformWraps.each(function (index, waveformWrap) {
            self.initWaveform(waveformWrap);
        });
    };

    this.redraw = function() {
        Waveforms.players.forEach(function (player) {
            player.waveform.drawer.fireEvent('redraw');
        });
    };

    this.initWaveform = function(wrap) {
        var self = this;
        var $wrap = $(wrap);
        var $player = $wrap.parents('.vocalizr-player, .js-vocalizr-player');
        var $playBtn = $player.find('.js-play-btn');
        var $playShape = $playBtn.find('.play-shape');
        var $playedDuration = $player.find('.track-current');
        var $duration = $player.find('.track-duration');
        var height = parseInt($wrap.data('height'));
        var peaks = $wrap.data('waveform');
        var audio = $wrap.data('audio');
        var playedUrl = $wrap.data('played-url');
        var $playedCounter = $($wrap.data('played-counter'));

        var ctx = document.createElement('canvas').getContext('2d');

        var gradientPreset = $wrap.data('progress-gradient-preset') ?? 'none';

        var progressGrd = ctx.createLinearGradient(0, 0, 0, height - 1);

        if (gradientPreset === "none") {
            progressGrd.addColorStop(0, 'rgba(44, 146, 191, 1)');
            progressGrd.addColorStop(0.495, 'rgba(44, 183, 199, 1)');
        } else if (gradientPreset === "paid-bid-option-1") {
            progressGrd.addColorStop(0, 'rgb(0,234,255)');
            // progressGrd.addColorStop(0, 'rgb(4,154,165)');
            progressGrd.addColorStop(0.495, 'rgb(201,242,255)');
        }

        /** @type {WaveSurfer} */
        var waveform = WaveSurfer.create({
            container: wrap,
            waveColor: $wrap.data('wave-color') ?? '#abb1b5',
            cursorColor: 'rgba(0, 0, 0, 0.01)',
            progressColor: $wrap.data('progress-color') ?? progressGrd,
            backend: 'MediaElement',
            barWidth: 1,
            barGap: $wrap.data('bar-gap') ?? 1,
            reflection: true,
            responsive: true,
            partialRender: true,
            height: height,
            hideScrollbar: true,
            reflectionGap: $wrap.data('reflection-gap') ?? 1,
            progressReflectionColor: $wrap.data('progress-reflection-color') ?? '#2c7084',
            waveReflectionColor: $wrap.data('reflection-color') ?? '#5c696f',
            waveGapColor: $wrap.data('gap-color') ?? '#4b5a61',
            progressGapColor: $wrap.data('progress-gap-color') ?? '#2c7585',
            minLoudness: 1,
            reflectionHeightCoefficient: $wrap.data('reflection-height') ?? 0.42,
        });

        var player = {
            element: $player,
            waveform: waveform
        };

        Waveforms.players.push(player);
        $wrap.addClass('initialized');

        waveform.params.renderer.prototype.drawBars = this.drawCustomBars;
        waveform.params.renderer.prototype.customRect = this.drawCustomRect;

        if (peaks.mode === 'deferred') {
            $.ajax({
                url: peaks.url,
                success: function (data) {
                    waveform.load('/plugins/waveforms/empty.mp3', data.peaks);
                    peaks.peaks = data.peaks;
                }
            });
        } else {
            waveform.load('/plugins/waveforms/empty.mp3', peaks.peaks);
        }

        var currentTime = 0;
        var duration = 0;
        waveform.on('audioprocess', function () {
            if (waveform.getDuration() !== duration) {
                duration = waveform.getDuration();
                $duration.text(self.formatTime(duration));
            }
            if (waveform.getCurrentTime() !== currentTime) {
                currentTime = waveform.getCurrentTime();
                $playedDuration.text(self.formatTime(currentTime));
            }
        });

        waveform.on('play', function () {
            self.stopCurrent(waveform);
            $playShape.addClass('paused');

            setTimeout(function () {
                $duration.removeClass('hidden');
                $playedDuration.removeClass('hidden');
            }, 1);
            var volWrapper = $('span.volume-wrapper');
            var currVolWrapper = $player.find('span.volume-wrapper');

            volWrapper.removeClass('current');
            currVolWrapper.addClass('current');
            $('span.volume-wrapper:not(.current)').fadeOut(200);
            currVolWrapper.fadeIn(300);
        });
        waveform.on('pause', function () {
            $duration.addClass('hidden');
            $playedDuration.addClass('hidden');
            $playShape.removeClass('paused');
        });
        waveform.on('error', function (errorMsg) {
            self.stopCurrent(waveform);
            $duration.addClass('hidden');
            $playedDuration.addClass('hidden');
            $playShape.removeClass('paused');
            $playBtn.addClass('error');
            $playBtn.html('<i class="fas fa-exclamation-triangle"></i>');
            console.warn('Wavesurfers error: ' + errorMsg);
            self.currentPlayer = null;
            $('#loading').hide();
            $player.find('.loading-gradient').attr('style', 'display: none;');
        });
        waveform.on('ready', function () {
            $player.find('.loading-gradient').attr('style', 'display: none;');
        });

        var initialized = false;

        $playBtn.click(function () {
            if (!initialized) {
                $player.find('audio').attr('src', audio);
                initialized = true;
                self.recordPlay(playedUrl, $playedCounter);
                waveform.on('ready', function () {
                    $('#loading').hide();
                    waveform.play();
                });
                try {
                    App.showLoading();
                } catch (e) {

                }
                waveform.loadMediaElement(audio, peaks.peaks);
            } else {
                var isPlaying = $playShape.hasClass('paused');

                if (isPlaying) {
                    waveform.pause();
                } else {
                    waveform.play();
                }
            }
        });
    };

    this.formatTime = function (sourceSeconds) {

        var minuteInt = parseInt(sourceSeconds / 60);
        var secondInt = parseInt(sourceSeconds % 60);

        minuteInt = minuteInt ? minuteInt : 0;
        secondInt = secondInt ? secondInt : 0;

        var minutes = minuteInt.toString();
        var seconds = secondInt.toString();

        if (seconds.length < 2) {
            seconds = '0' + seconds;
        }

        return minutes + ':' + seconds;
    };

    this.stopCurrent = function (newPlayer) {
        if (this.currentPlayer !== null && this.currentPlayer !== newPlayer) {
            try {
                this.currentPlayer.stop();
            } catch (e) {
                console.warn('Could not stop player: ' + e.message);
            }
        }

        this.currentPlayer = newPlayer;
    };

    this.recordPlay = function (url, $counter) {
        if (!url) {
            return;
        }

        $.ajax({
            url: url,
            success: function (responseObject) {
                if (responseObject.count && $counter && $counter.length > 0) {
                    $counter.text(responseObject.count);
                }
            }
        });
    };

    this.drawCustomBars = function (peaks, channelIndex, start, end) {
        var drawer = this;
        return this.prepareDraw(
            peaks,
            channelIndex,
            start,
            end,
            function ({ absmax, hasMinVals, height, offsetY, halfH, peaks }) {
                // if drawBars was called within ws.empty we don't pass a start and
                // don't want anything to happen
                if (start === undefined) {
                    return;
                }
                // Skip every other value if there are negatives.
                var peakIndexScale = hasMinVals ? 2 : 1;
                var length = peaks.length / peakIndexScale;
                var bar = drawer.params.barWidth * drawer.params.pixelRatio;
                var gap =
                    drawer.params.barGap === null
                        ? Math.max(drawer.params.pixelRatio, ~~(bar / 2))
                        : Math.max(
                        drawer.params.pixelRatio,
                        drawer.params.barGap * drawer.params.pixelRatio
                        );
                var step = bar + gap;

                var scale = length / drawer.width;
                var first = start;
                var last = end;
                var i;

                for (i = first; i < last; i += step) {
                    var peak = peaks[Math.floor(i * scale * peakIndexScale)] || 0;
                    var nextPeak = peaks[Math.floor((i + step) * scale * peakIndexScale)] || 0;
                    var h = Math.round((peak / absmax) * halfH);

                    h = h >= drawer.params.minLoudness ? h : drawer.params.minLoudness;

                    var barX = i + drawer.halfPixel,
                        barY = halfH - h + offsetY,
                        barW = bar + drawer.halfPixel;

                    //h + (h * 0.42)
                    drawer.fillRect(barX, barY, barW, h);

                    drawer.customRect(barX, barY + h + drawer.params.reflectionGap, barW, h * drawer.params.reflectionHeightCoefficient, [
                        drawer.params.waveReflectionColor,
                        drawer.params.progressReflectionColor
                    ]);

                    var betweenH = nextPeak > peak ? h : Math.round((nextPeak / absmax) * halfH);

                    drawer.customRect(barX + barW, halfH - betweenH + offsetY, gap + drawer.halfPixel, betweenH, [
                        drawer.params.waveGapColor,
                        drawer.params.progressGapColor
                    ]);
                }
            }
        );
    };
    
    this.drawCustomRect = function (x, y, width, height, fillStyles) {
        var drawer = this;
        var startCanvas = Math.floor(x / drawer.maxCanvasWidth);
        var endCanvas = Math.min(
            Math.ceil((x + width) / drawer.maxCanvasWidth) + 1,
            drawer.canvases.length
        );
        var i;
        for (i = startCanvas; i < endCanvas; i++) {
            var entry = drawer.canvases[i];
            var leftOffset = i * drawer.maxCanvasWidth;

            var intersection = {
                x1: Math.max(x, i * drawer.maxCanvasWidth),
                y1: y,
                x2: Math.min(
                    x + width,
                    i * drawer.maxCanvasWidth + entry.waveCtx.canvas.width
                ),
                y2: y + height
            };

            if (intersection.x1 < intersection.x2) {
                drawer.setFillStyles(entry);

                var swapWaveFillStyle = entry.waveCtx.fillStyle;
                var swapProgressFillStyle = entry.progressCtx.fillStyle;

                entry.waveCtx.fillStyle = fillStyles[0];
                entry.progressCtx.fillStyle = fillStyles[1];

                drawer.fillRectToContext(
                    entry.waveCtx,
                    intersection.x1 - leftOffset,
                    intersection.y1,
                    intersection.x2 - intersection.x1,
                    intersection.y2 - intersection.y1
                );

                drawer.fillRectToContext(
                    entry.progressCtx,
                    intersection.x1 - leftOffset,
                    intersection.y1,
                    intersection.x2 - intersection.x1,
                    intersection.y2 - intersection.y1
                );

                entry.waveCtx.fillStyle = swapWaveFillStyle;
                entry.progressCtx.fillStyle = swapProgressFillStyle;
            }
        }
    };

    this.setVolume = function (volume) {
        Waveforms.players.forEach(function (player) {
            player.waveform.setVolume(volume);
        });
    }
}

//TODO: volume across
$(function () {
    window.waveforms = new Waveforms();

    waveforms.init();

    $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
        $(window).trigger('waveform-redraw');
    });

    window.volume = 1;

    $('input#volume-slider').on('input change', function (e) {
        var volume = $(this).val();
        window.volume = volume;
        waveforms.setVolume(volume);
        $('input#volume-slider').val(volume);
    });
});