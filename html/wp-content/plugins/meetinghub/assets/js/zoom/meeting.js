window.addEventListener("DOMContentLoaded", function (event) {
  console.log("DOM fully loaded and parsed");
});

function websdkready() {
  var testTool = window.testTool;
  var meetingConfig = {
    apiKey: API_KEY,
    secretKey: SECRET_KEY,
    meetingNumber: meeting_id,
    userName: window.username, // Using the name from form
    passWord: meeting_password,
    leaveUrl: leaveUrl,
    role: window.role,
    userEmail: window.email,
    lang: window.lang, // Using the language from form
    signature: "",
    china: 0,
  };

  console.log(meetingConfig);

  if (testTool.isMobileDevice()) {
    vConsole = new VConsole();
  }
  console.log(JSON.stringify(ZoomMtg.checkSystemRequirements()));

  if (meetingConfig.china)
    ZoomMtg.setZoomJSLib("https://jssdk.zoomus.cn/3.1.6/lib", "/av");

  ZoomMtg.preLoadWasm();
  ZoomMtg.prepareWebSDK();

  function beginJoin() {
    var signature = ZoomMtg.generateSDKSignature({
      meetingNumber: meetingConfig.meetingNumber,
      sdkKey: meetingConfig.apiKey,
      sdkSecret: meetingConfig.secretKey,
      role: meetingConfig.role,
      success: function (res) {
        meetingConfig.signature = res.result;
        meetingConfig.sdkKey = meetingConfig.apiKey;
      },
    });

    ZoomMtg.i18n.load(meetingConfig.lang);
    ZoomMtg.init({
      leaveUrl: meetingConfig.leaveUrl,
      webEndpoint: meetingConfig.webEndpoint,
      disableCORP: !window.crossOriginIsolated,
      externalLinkPage: "./externalLinkPage.html",
      success: function () {
        console.log(meetingConfig);
        console.log("signature", signature);

        ZoomMtg.join({
          meetingNumber: meetingConfig.meetingNumber,
          userName: meetingConfig.userName,
          signature: signature,
          sdkKey: meetingConfig.sdkKey,
          userEmail: meetingConfig.userEmail,
          passWord: meetingConfig.passWord,
          success: function (res) {
            console.log("join meeting success");
            ZoomMtg.getAttendeeslist({});
            ZoomMtg.getCurrentUser({
              success: function (res) {
                console.log("success getCurrentUser", res.result.currentUser);
              },
            });
          },
          error: function (res) {
            console.log(res);
          },
        });
      },
      error: function (res) {
        console.log(res);
      },
    });

    // Event listeners
    ZoomMtg.inMeetingServiceListener("onUserJoin", function (data) {
      console.log("inMeetingServiceListener onUserJoin", data);
    });

    ZoomMtg.inMeetingServiceListener("onUserLeave", function (data) {
      console.log("inMeetingServiceListener onUserLeave", data);
    });

    ZoomMtg.inMeetingServiceListener("onUserIsInWaitingRoom", function (data) {
      console.log("inMeetingServiceListener onUserIsInWaitingRoom", data);
    });

    ZoomMtg.inMeetingServiceListener("onMeetingStatus", function (data) {
      console.log("inMeetingServiceListener onMeetingStatus", data);
    });
  }

  beginJoin();
}