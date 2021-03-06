angular.module('openITCOCKPIT').directive('hostsDowntimeWidget', function($http, $interval){
    return {
        restrict: 'E',
        templateUrl: '/dashboards/hostsDowntimeWidget.html',
        scope: {
            'widget': '='
        },

        controller: function($scope){
            $scope.interval = null;
            $scope.init = true;
            $scope.useScroll = true;
            $scope.scroll_interval = 30000;

            var $widget = $('#widget-' + $scope.widget.id);

            $widget.on('resize', function(event, items){
                hasResize();
            });

            $scope.hostListTimeout = null;

            $scope.sort = 'DowntimeHost.scheduled_start_time';
            $scope.direction = 'desc';
            $scope.currentPage = 1;

            /*** Filter Settings ***/
            $scope.filter = {
                DowntimeHost: {
                    comment_data: '',
                    was_cancelled: false,
                    was_not_cancelled: false
                },
                Host: {
                    name: ''
                },
                isRunning: false,
                hideExpired: true
            };
            /*** Filter end ***/

            var loadWidgetConfig = function(){
                $http.get("/dashboards/hostsDowntimeWidget.json?angular=true&widgetId=" + $scope.widget.id, $scope.filter).then(function(result){
                    $scope.filter.Host = result.data.config.Host;
                    $scope.filter.DowntimeHost = result.data.config.DowntimeHost;
                    $scope.filter.isRunning = result.data.config.isRunning;
                    $scope.filter.hideExpired = result.data.config.hideExpired;
                    $scope.direction = result.data.config.direction;
                    $scope.sort = result.data.config.sort;
                    $scope.useScroll = result.data.config.useScroll;
                    var scrollInterval = parseInt(result.data.config.scroll_interval);
                    if(scrollInterval < 5000){
                        scrollInterval = 5000;
                    }
                    $scope.scroll_interval = scrollInterval;
                    $scope.load();
                });
            };

            $scope.$on('$destroy', function(){
                $scope.pauseScroll();
            });

            $scope.load = function(options){

                options = options || {};
                options.save = options.save || false;

                var wasCancelled = '';
                if($scope.filter.DowntimeHost.was_cancelled ^ $scope.filter.DowntimeHost.was_not_cancelled){
                    wasCancelled = $scope.filter.DowntimeHost.was_cancelled === true;
                }

                var params = {
                    'angular': true,
                    'scroll': $scope.useScroll,
                    'sort': $scope.sort,
                    'page': $scope.currentPage,
                    'direction': $scope.direction,
                    'filter[DowntimeHost.comment_data]': $scope.filter.DowntimeHost.comment_data,
                    'filter[DowntimeHost.was_cancelled]': wasCancelled,
                    'filter[Host.name]': $scope.filter.Host.name,
                    'filter[hideExpired]': $scope.filter.hideExpired,
                    'filter[isRunning]': $scope.filter.isRunning
                };

                $http.get("/downtimes/host.json", {
                    params: params
                }).then(function(result){
                    $scope.downtimes = result.data.all_host_downtimes;
                    $scope.paging = result.data.paging;
                    $scope.scroll = result.data.scroll;

                    if(options.save === true){
                        saveSettings(params);
                    }

                    if($scope.useScroll){
                        $scope.startScroll();
                    }
                    $scope.init = false;
                });
            };

            $scope.getSortClass = function(field){
                if(field === $scope.sort){
                    if($scope.direction === 'asc'){
                        return 'fa-sort-asc';
                    }
                    return 'fa-sort-desc';
                }
                return 'fa-sort';
            };

            $scope.orderBy = function(field){
                if(field !== $scope.sort){
                    $scope.direction = 'asc';
                    $scope.sort = field;
                    $scope.load();
                    return;
                }

                if($scope.direction === 'asc'){
                    $scope.direction = 'desc';
                }else{
                    $scope.direction = 'asc';
                }
                $scope.load();
            };

            var hasResize = function(){
                if($scope.hostListTimeout){
                    clearTimeout($scope.hostListTimeout);
                }
                $scope.hostListTimeout = setTimeout(function(){
                    $scope.hostListTimeout = null;
                    $scope.limit = getLimit($widget.height());
                    if($scope.limit <= 0){
                        $scope.limit = 1;
                    }
                    $scope.load();
                }, 500);
            };

            $scope.startScroll = function(){
                $scope.pauseScroll();
                $scope.useScroll = true;

                $scope.interval = $interval(function(){
                    var page = $scope.currentPage;
                    if($scope.scroll.hasNextPage){
                        page++;
                    }else{
                        page = 1;
                    }
                    $scope.changepage(page)
                }, $scope.scroll_interval);

            };

            $scope.pauseScroll = function(){
                if($scope.interval !== null){
                    $interval.cancel($scope.interval);
                    $scope.interval = null;
                }
                $scope.useScroll = false;
            };

            var getLimit = function(height){
                height = height - 34 - 128 - 61 - 10 - 37; //Unit: px
                //                ^ widget Header
                //                     ^ Widget filter
                //                           ^ Paginator
                //                                ^ Margin between header and table
                //                                     ^ Table header

                var limit = Math.floor(height / 36); // 36px = table row height;
                if(limit <= 0){
                    limit = 1;
                }
                return limit;
            };

            var saveSettings = function(){
                var settings = $scope.filter;
                settings['scroll_interval'] = $scope.scroll_interval;
                settings['useScroll'] = $scope.useScroll;
                $http.post("/dashboards/hostsDowntimeWidget.json?angular=true&widgetId=" + $scope.widget.id, settings).then(function(result){
                    return true;
                });
            };

            var getTimeString = function(){
                return (new Date($scope.scroll_interval * 60)).toUTCString().match(/(\d\d:\d\d)/)[0] + " minutes";
            };

            $scope.changepage = function(page){
                if(page !== $scope.currentPage){
                    $scope.currentPage = page;
                    $scope.load();
                }
            };

            $scope.limit = getLimit($widget.height());

            loadWidgetConfig();

            $scope.$watch('filter', function(){
                $scope.currentPage = 1;
                if($scope.init === true){
                    return true;
                }

                $scope.load({
                    save: true
                });
            }, true);

            $scope.$watch('scroll_interval', function(){
                $scope.pagingTimeString = getTimeString();
                if($scope.init === true){
                    return true;
                }
                $scope.pauseScroll();
                $scope.startScroll();
                $scope.load({
                    save: true
                });
            });
        },

        link: function($scope, element, attr){

        }
    };
});
