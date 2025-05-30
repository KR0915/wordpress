window.addEventListener("DOMContentLoaded", function (event) {
  console.log("DOM fully loaded and parsed");
  websdkready();
});

function websdkready() {
  var testTool = window.testTool;
  if (testTool.isMobileDevice()) {
    vConsole = new VConsole();
  }
  console.log("checkSystemRequirements");
  console.log(JSON.stringify(ZoomMtg.checkSystemRequirements()));
  
  ZoomMtg.preLoadWasm();
  ZoomMtg.prepareWebSDK();

  // it's option if you want to change the MeetingSDK-Web dependency link resources. setZoomJSLib must be run at first
  // ZoomMtg.setZoomJSLib("https://source.zoom.us/{VERSION}/lib", "/av"); // default, don't need call it
  // ZoomMtg.setZoomJSLib("https://jssdk.zoomus.cn/{VERSION}/lib", "/av"); // china cdn option
  ZoomMtg.preLoadWasm(); // pre download wasm file to save time.

  /**
   * NEVER PUT YOUR ACTUAL SDK SECRET OR CLIENT SECRET IN CLIENT SIDE CODE, THIS IS JUST FOR QUICK PROTOTYPING
   * The below generateSignature should be done server side as not to expose your SDK SECRET in public
   * You can find an example in here: https://developers.zoom.us/docs/meeting-sdk/auth/#signature
   */

  // some help code, remember mn, pwd, lang to cookie, and autofill.
  document.getElementById("display_name").value =
    "CDN" +
    ZoomMtg.getWebSDKVersion()[0] +
    testTool.detectOS() +
    "#" +
    testTool.getBrowserInfo();
  document.getElementById("meeting_number").value =
    testTool.getCookie("meeting_number");
  document.getElementById("meeting_pwd").value =
    testTool.getCookie("meeting_pwd");
  if (testTool.getCookie("meeting_lang"))
    document.getElementById("meeting_lang").value =
      testTool.getCookie("meeting_lang");

}