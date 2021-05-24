(function() {

    VocalizrAdmin = {
        templates: {
            loading: '<div class="loading">Loading...</div>',
            noResults: '<div class="no-results">No results found matching your search.</div>'
        },

        initUserAdminSearch: function() {
            var url = $('#admin-user-search-form').attr('action');

            $('.admin-users').on('click', '.btn-activate, .btn-certify, .btn-upgrade[data-role=downgrade], .btn-verify', function(e) {
                e.preventDefault();
                var el = $(this);
                $.ajax({
                    dataType: "json",
                    type: "GET",
                    url:  el.data('href'),
                    success: function(data) {
                            if (data.success) {
                                $('#user-result-' + data.userId).html($(data.html).html());
                            } else if (data.error) {
                                alert(data.error);
                            }
                    }
                });
            });

            var $upgradeForm = $('#user-subscription-form');
            var $upgradeModal = $('#upgrade_modal');
            $('.admin-users').on('click', '.btn-upgrade[data-role=upgrade]', function(e) {
                e.preventDefault();
                var $btn = $(this);

                $upgradeForm.find('button[type=reset]').click();
                $upgradeForm.attr('action', $btn.data('href'));
                $upgradeModal.modal('show');
            });

            $upgradeForm.submit(function (e) {
                e.stopPropagation();
                e.preventDefault();
                $.ajax({
                    data: $upgradeForm.serialize(),
                    type: "POST",
                    url:  $upgradeForm.attr('action'),
                    success: function(data) {
                        if (data.success) {
                            $('#user-result-' + data.userId).html($(data.html).html());
                            $upgradeModal.modal('hide');
                        } else if (data.error) {
                            $upgradeModal.modal('hide');
                            alert(data.error);
                        }
                    }
                });
            });

            $('.admin-users').on('click', '#user-search-btn', function(e) {
            var searchTerm = $('#search-term').val();
            e.preventDefault();
            if (searchTerm) {
                $('.results-container').empty();
                $('.results-container').append($(VocalizrAdmin.templates.loading));
                $.ajax({
                    dataType: "json",
                    type: "POST",
                    url:  url,
                    data: $('#admin-user-search-form').serialize(),
                    success: function(data)
                    {
                        if (data.success) {
                            $('.results-container').empty();
                            if (data.numResults === 0) {
                                $('.results-container').append($(VocalizrAdmin.templates.noResults));
                            } else {
                                $('.results-container').append(data.html);
                            }
                        }
                    }
                });
            }



            });
        },

        initProjectAdminSearch: function() {
            var url = $('#admin-project-search-form').attr('action');


            $('.admin-projects').on('click', '.btn-activate', function(e) {
                e.preventDefault();
                var el = $(this);
                $.ajax({
                        dataType: "json",
                        type: "GET",
                        url:  el.data('href'),
                        success: function(data)
                        {
                            if (data.success) {
                                $('#project-result-' + data.projectId).html($(data.html).html());
                            } else {
                                $('#activate-error').remove();
                                $('#project-result-' + data.projectId + ' .actions').append('<div id="activate-error" style="color: #d9534f;">' + data.message + '</div>');
                            }
                        }
                    });
            });

            $('.admin-projects').on('click', '.btn-resend-employer-receipt', function(e) {
                e.preventDefault();
                var el = $(this);
                $.ajax({
                    dataType: "json",
                    type: "GET",
                    url:  el.data('href'),
                    success: function(data)
                    {
                        if (data.success) {
                            $('#project-result-' + data.projectId).html($(data.html).html());
                        }
                    }
                });
            });

            $('.admin-projects').on('click', '#project-search-btn', function(e) {
              console.log('clicked');
                var searchTerm = $('#search-term').val();
                e.preventDefault();
                if (searchTerm) {
                    $('.results-container').empty();
                    $('.results-container').append($(VocalizrAdmin.templates.loading));
                    $.ajax({
                        dataType: "json",
                        type: "POST",
                        url:  url,
                        data: {
                            s: $('#search-term').val()
                        },
                        success: function(data)
                        {
                            if (data.success) {
                                $('.results-container').empty();
                                if (data.numResults === 0) {
                                    $('.results-container').append($(VocalizrAdmin.templates.noResults));
                                } else {
                                    $('.results-container').append(data.html);
                                }
                            }
                        }
                    });
                }
            });
        },

        initWithdrawEmail: function () {
            var $results = $('.results-container');
            var $searchForm = $('form.admin-search-form');
            $searchForm.submit(function (event) {
                event.stopPropagation();
                event.preventDefault();
                $results.empty();
                $.ajax({
                    method: 'POST',
                    url: $(this).attr('action'),
                    data: $(this).serialize(),
                    success: function (response) {
                        if (typeof response.html !== "undefined") {
                            if (response.numResults === 0) {
                                alert('No results.');
                            }
                            $results.html(response.html);
                        } else if (response.message) {
                            $results.text(response.message);
                        } else {
                            alert('Could not recognize backend response.');
                        }
                    }
                });
                return false;
            });

            $results.on('click', '.change-withdraw-email', function (e) {
                e.stopPropagation();
                e.preventDefault();
                var $button = $(this);
                var $result = $button.parents('.result');
                $.ajax({
                    url: $button.data('url'),
                    data: {
                        id: $result.data('id'),
                        email: $result.find('input.withdraw-email').val()
                    },
                    success: function (response) {
                        if (response.success) {
                            alert('Email changed');
                            $searchForm.trigger('submit');
                        } else {
                            alert('Could not change email');
                        }
                    },
                    error: function (xhr) {
                        if (xhr.status === 400) {
                            alert('Could not change email: validation failed.');
                        } else {
                            alert('Could not change email: server returned ' + xhr.status + ' status.')
                        }
                    }
                });
            });
        },
        initReview: function () {
            var $results = $('.results-container');
            var $searchForm = $('form.admin-search-form');
            $searchForm.submit(function (event) {
                event.stopPropagation();
                event.preventDefault();
                $results.empty();
                $.ajax({
                    method: 'POST',
                    url: $(this).attr('action'),
                    data: $(this).serialize(),
                    success: function (response) {
                        if (typeof response.html !== "undefined") {
                            if (response.numResults === 0 || $.trim(response.html) == "") {
                                alert('No results.');
                            }
                            $results.html(response.html);
                        } else if (response.message) {
                            $results.text(response.message);
                        } else {
                            alert('Could not recognize backend response.');
                        }
                    }
                });
                return false;
            });

            $results.on('click', '.change-review', function (e) {
                e.stopPropagation();
                e.preventDefault();
                var $button = $(this);
                var $result = $button.parents('.result');
                $.ajax({
                    url: $button.data('url'),
                    data: {
                        id: $result.data('id'),
                        review: $result.find('textarea.review').val()
                    },
                    success: function (response) {
                        if (response.success) {
                            alert('Review changed');
                            $searchForm.trigger('submit');
                        } else {
                            alert('Could not change review');
                        }
                    },
                    error: function (xhr) {
                        if (xhr.status === 400) {
                            alert('Could not change review: validation failed.');
                        } else {
                            alert('Could not change review: server returned ' + xhr.status + ' status.')
                        }
                    }
                });
            });
            $results.on('click', '.delete-review', function (e) {
                e.stopPropagation();
                e.preventDefault();
                var $button = $(this);
                var $result = $button.parents('.result');
                $.ajax({
                    url: $button.data('url'),
                    data: {
                        id: $result.data('id')
                    },
                    success: function (response) {
                        if (response.success) {
                            alert('Review deleted');
                            $searchForm.trigger('submit');
                        } else {
                            alert('Could not delete review');
                        }
                    },
                    error: function (xhr) {
                        if (xhr.status === 400) {
                            alert('Could not delete review: validation failed.');
                        } else {
                            alert('Could not delete review: server returned ' + xhr.status + ' status.')
                        }
                    }
                });
            });
        },
        initPassword: function () {
            var $results = $('.results-container');
            var $searchForm = $('form.admin-search-form');
            $searchForm.submit(function (event) {
                event.stopPropagation();
                event.preventDefault();
                $results.empty();
                $.ajax({
                    method: 'POST',
                    url: $(this).attr('action'),
                    data: $(this).serialize(),
                    success: function (response) {
                        if (typeof response.html !== "undefined") {
                            if (response.numResults === 0) {
                                alert('No results.');
                            }
                            $results.html(response.html);
                        } else if (response.message) {
                            $results.text(response.message);
                        } else {
                            alert('Could not recognize backend response.');
                        }
                    }
                });
                return false;
            });

            $results.on('click', '.change-password', function (e) {
                e.stopPropagation();
                e.preventDefault();
                var $button = $(this);
                var $result = $button.parents('.result');
                $.ajax({
                    url: $button.data('url'),
                    data: {
                        id: $result.data('id'),
                        password: $result.find('input.password').val()
                    },
                    success: function (response) {
                        if (response.success) {
                            alert('Review changed');
                            $searchForm.trigger('submit');
                        } else {
                            alert('Could not change review');
                        }
                    },
                    error: function (xhr) {
                        if (xhr.status === 400) {
                            alert('Could not change review: validation failed.');
                        } else {
                            alert('Could not change review: server returned ' + xhr.status + ' status.')
                        }
                    }
                });
            });
        },
        initGstGigCommissions: function () {
            var $results = $('#gig-commissions');
            var $searchForm = $('form#gig-commissions-form');
            $searchForm.submit(function (event) {
                event.stopPropagation();
                event.preventDefault();
                $results.empty();
                $.ajax({
                    method: 'POST',
                    url: $(this).attr('action'),
                    data: $(this).serialize(),
                    success: function (response) {
                        if (typeof response.html !== "undefined") {
                            if (response.numResults === 0) {
                                alert('No results.');
                            }
                            $results.html(response.html);
                        } else if (response.message) {
                            $results.text(response.message);
                        } else {
                            alert('Could not recognize backend response.');
                        }
                    }
                });
                return false;
            });

        },
        initGstUpgrades: function () {
            var $results = $('#upgrades');
            var $searchForm = $('form#upgrades-form');
            $searchForm.submit(function (event) {
                event.stopPropagation();
                event.preventDefault();
                $results.empty();
                $.ajax({
                    method: 'POST',
                    url: $(this).attr('action'),
                    data: $(this).serialize(),
                    success: function (response) {
                        if (typeof response.html !== "undefined") {
                            if (response.numResults === 0) {
                                alert('No results.');
                            }
                            $results.html(response.html);
                        } else if (response.message) {
                            $results.text(response.message);
                        } else {
                            alert('Could not recognize backend response.');
                        }
                    }
                });
                return false;
            });

        },
        initGstSubscriptions: function () {
            var $results = $('#subscriptions');
            var $searchForm = $('form#subscriptions-form');
            $searchForm.submit(function (event) {
                event.stopPropagation();
                event.preventDefault();
                $results.empty();
                $.ajax({
                    method: 'POST',
                    url: $(this).attr('action'),
                    data: $(this).serialize(),
                    success: function (response) {
                        if (typeof response.html !== "undefined") {
                            if (response.numResults === 0) {
                                alert('No results.');
                            }
                            $results.html(response.html);
                        } else if (response.message) {
                            $results.text(response.message);
                        } else {
                            alert('Could not recognize backend response.');
                        }
                    }
                });
                return false;
            });

        }
    };

    VocalizrDashboard = {

        maxBarHeight: 219,

        loadedStats: [],

        templates: {
            loading: '<div class="loading-stats">Loading</div>',
            wrapper: '<div class="stats-wrap"></div>',
            statColumn: '<div class="stat-col"></div>',
            statValue: '<div class="stat-value"></div>',
            statBar: '<div class="stat-bar"></div>',
            statLabel: '<div class="stat-label"></div>'
        },

        initDashboard: function() {
            $('.admin-dashboard').on('change', '#stat-category', function() {
                VocalizrDashboard.processStats();
            });
            $('.admin-dashboard').on('click', '#stat-update-btn', function() {
                VocalizrDashboard.getStats();
            });
            VocalizrDashboard.getStats();
        },

        getStats: function() {
            var container = $('#stats-graph-container');
            var url = container.data('url');
            $.ajax({
                    dataType: "json",
                    type: "POST",
                    url:  url,
                    data: {
                        category: $('#stat-category').val(),
                        range: $('#stat-range').val(),
                        startDate: $('#stat-start-date').val(),
                        endDate: $('#stat-end-date').val(),
                    },
                    success: function(data)
                    {
                        if (data.success) {
                            var stats = data.stats;
                            VocalizrDashboard.loadedStats = stats;
                            VocalizrDashboard.processStats();
                        }
                    }
                });
        },

        processStats: function() {
            var container = $('#stats-graph-container');
            var wrapper = $(VocalizrDashboard.templates.wrapper);
            var maxStatValue = 0;
            var statCategory = $('#stat-category').val();


            // inital loop to get the max stat value. Sucks having to loop twice
            // but not sure of better way
            VocalizrDashboard.loadedStats.forEach(function(item) {
                if (item[statCategory] > maxStatValue) {
                    maxStatValue = item[statCategory];
                }
            });

            VocalizrDashboard.loadedStats.forEach(function(item) {
                var itemValue = $(VocalizrDashboard.templates.statValue);
                var itemBar = $(VocalizrDashboard.templates.statBar);
                var itemLabel = $(VocalizrDashboard.templates.statLabel);
                var statCol = $(VocalizrDashboard.templates.statColumn);
                var barHeight = 0;

                if (maxStatValue > 0) {
                    barHeight = (item[statCategory] / maxStatValue) * VocalizrDashboard.maxBarHeight;
                }

                if (statCategory == 'revenue') {
                    itemValue.html('$' + item[statCategory] / 100);
                } else {
                    itemValue.html(item[statCategory]);
                }

                if (barHeight == 0) {
                    itemValue.css('margin-top', VocalizrDashboard.maxBarHeight - 1);
                    itemBar.css('height', 1);
                } else {
                    itemValue.css('margin-top', VocalizrDashboard.maxBarHeight - barHeight);
                    itemBar.css('height', barHeight);
                }
                itemLabel.html(item.label);


                itemValue.appendTo(statCol);
                itemBar.appendTo(statCol);
                itemLabel.appendTo(statCol);
                statCol.appendTo(wrapper);
            });
            container.empty();
            wrapper.css('width', (VocalizrDashboard.loadedStats.length) * 61);
            container.append(wrapper);
        }
    }
})();
