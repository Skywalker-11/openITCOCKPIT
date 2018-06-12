angular.module('openITCOCKPIT')
    .controller('HostsAddwizardController', function($scope, $http){
        $scope.post = {
            Container: {
                Container: [],
                container_id: 0
            },
            Host: {
                name: '',
                address: '',
                satellite_id: 0,
                own_contacts: 0,
                own_contactgroups: 0,
                own_customvariables: 0,
                description: '',
                host_url: '',
                hosttemplate_id: 0,
                Contact: [],
                Contactgroup: [],
                Hostgroup: [],
                Parenthost: [],
                uuid: ''
            },
            Contact: {
                Contact: []
            },
            Contactgroup: {
                Contactgroup: []
            },
            Parenthost: {
                Parenthost: []
            },
            Hostcommandargumentvalue: [],
            Customvariable: []
        };

        $scope.selectedContainer = 0;
        $scope.hostname = 0;
        $scope.hostaddress = 0;
        $scope.init = true;
        $scope.load = function(){
            $http.get("/hosts/loadContainers.json", {
                params: {
                    'angular': true
                }
            }).then(function(result){
                $scope.containers = result.data.containers;
                $scope.init = false;
            });
        };

        $scope.containerSelected = function(){
            $scope.selectedContainer = $scope.post.Container.container_id;
        };


        $scope.loadData = function(){
            if($scope.init){
                return;
            }
            $http.get("/hosts/loadElementsByContainerId/" + $scope.selectedContainer + ".json", {
                params: {
                    'angular': true,
                    //'containerId': $scope.selectedContainer,
                    //'filter[Hosttemplate.name]': searchString,
                    //'selected[]': $scope.post.Hostgroup.Hosttemplate
                }
            }).then(function(result){
                $scope.hosttemplates = result.data.hosttemplates;
                $scope.contacts = result.data.contacts;
                $scope.contactgroups = result.data.contactgroups;
            });
        };

        $scope.getHostname = function(){
            //gethostnamebyaddr
            console.log($scope.address);
            if(!$scope.address){
                return;
            }
            $http.get('/hosts/gethostnamebyaddr/' + $scope.address + '.json', {
                params: {
                    'angular': true,
                }
            }).then(function(result){
                console.log(result.data);
                $scope.result = result.data;
            });
        };

        $scope.getHostip = function(){
            //gethostipbyname
            console.log($scope.hostname);
            if(!$scope.hostname){
                return;
            }
            $http.get('/hosts/gethostipbyname/' + $scope.hostname + '.json', {
                params: {
                    'angular': true,
                }
            }).then(function(result){
                console.log(result.data);
                $scope.result = result.data;
            });
        };


        /*        $scope.submit = function(){
                    $http.post("/hosts/addwizard.json?angular=true",
                        $scope.post
                    ).then(function(result){
                        console.log('Data saved successfully');
                        window.location.href = '/hostgroups/index';
                    }, function errorCallback(result){
                        if(result.data.hasOwnProperty('error')){
                            $scope.errors = result.data.error;
                        }
                    });

                };
        */

        $scope.$watch('selectedContainer', function(){
            if($scope.init){
                return;
            }
            $scope.loadData('');
        }, true);

        $scope.$watch('post.Host.name', function(){
            console.log($scope.post.Host.name);
            $scope.hostname = $scope.post.Host.name;
            $scope.getHostip();

        }, true);

        $scope.$watch('post.Host.address', function(){
            console.log($scope.post.Host.address);
            $scope.address = $scope.post.Host.address;
            $scope.getHostname();
        }, true);

        $scope.load();

    });