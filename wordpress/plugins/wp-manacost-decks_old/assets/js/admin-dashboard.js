jQuery(function($) {
    var modal = $('#unified-hs-archetype-trend-modal');
    var lastTrigger = null;

    if (!modal.length) {
        return;
    }

    function numberFormat(value) {
        var number = Number(value) || 0;
        return number.toLocaleString('ru-RU');
    }

    function parseTrendData(trigger) {
        try {
            return JSON.parse(trigger.getAttribute('data-trend') || '{}');
        } catch (error) {
            return {};
        }
    }

    function renderChart(days) {
        var chart = modal.find('[data-trend-chart]');
        chart.empty();

        if (!Array.isArray(days) || !days.length) {
            return 0;
        }

        var max = days.reduce(function(carry, day) {
            return Math.max(carry, Number(day.copies) || 0);
        }, 0);

        days.forEach(function(day) {
            var copies = Number(day.copies) || 0;
            var height = max > 0 ? Math.max(6, Math.min(100, Math.round((copies / max) * 100))) : 6;
            var item = $('<div/>', {
                class: 'unified-hs-trend-day',
                title: day.day + ': ' + numberFormat(copies)
            });

            $('<span/>').css('height', height + '%').appendTo(item);
            $('<em/>').text(numberFormat(copies)).appendTo(item);
            chart.append(item);
        });

        return max;
    }

    function openTrendModal(data) {
        var title = data.title || '';
        var total = Number(data.total) || 0;
        var currentTotal = Number(data.currentTotal) || 0;

        modal.find('[data-trend-title]').text('Тренд архетипа: ' + title);
        modal.find('[data-trend-description]').text('Копирования кода по дням за период ' + (data.from || '') + ' - ' + (data.to || '') + '.');
        modal.find('[data-trend-period-total]').text(numberFormat(total) + ' за период');
        modal.find('[data-trend-current-total]').text(numberFormat(currentTotal) + ' всего в карточках');

        renderChart(data.days || []);

        var empty = modal.find('[data-trend-empty]');
        if (total <= 0) {
            empty.text('По этому архетипу пока нет дневных событий копирования за выбранный период.').prop('hidden', false);
        } else {
            empty.prop('hidden', true);
        }

        modal.addClass('is-open').attr('aria-hidden', 'false');
        $('body').addClass('unified-hs-modal-open');
        modal.find('[data-trend-close]').trigger('focus');
    }

    function closeTrendModal() {
        modal.removeClass('is-open').attr('aria-hidden', 'true');
        $('body').removeClass('unified-hs-modal-open');

        if (lastTrigger) {
            lastTrigger.focus();
        }
    }

    $(document).on('click', '.unified-hs-trend-trigger', function(event) {
        event.preventDefault();
        lastTrigger = this;
        openTrendModal(parseTrendData(this));
    });

    modal.on('click', function(event) {
        if (event.target === this) {
            closeTrendModal();
        }
    });

    modal.on('click', '[data-trend-close]', closeTrendModal);

    $(document).on('keyup', function(event) {
        if (event.key === 'Escape' && modal.hasClass('is-open')) {
            closeTrendModal();
        }
    });
});
