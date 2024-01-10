//check device type
function mobileType (){
	const userAgent = window.navigator.userAgent.toLowerCase();
	if(/iphone|ipad|ipod/.test( userAgent )){
		return "ios";
	} else if(/android/.test( userAgent )){
		return "android";
	}
}

//check if pwa is already installed
function pwaInstalled(){
	if(('standalone' in window.navigator) && (window.navigator.standalone)){
		return true
	}else{
		return false
	}
}

// service worker
function registerServiceWorker() {
  if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('/service-worker.js').then(function(registration) {
      console.log('Service worker registration succeeded:', registration);
      return true;
    },function(error) {
      console.log('Service worker registration failed:', error);
      return false;
    });
  } else {
    console.log('Service workers are not supported.');
    return false;
  }
}