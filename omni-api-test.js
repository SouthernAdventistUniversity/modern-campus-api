(async () => {
  const calUser = "api-calendar";
  const calPass = "1892api423Calendar!";

  const connect = async () => {
    const body = new URLSearchParams();
    body.append("skin", "oucampus");
    body.append("account", "southern");
    body.append("username", calUser);
    body.append("password", calPass);
    const response = await fetch(
      "https://a.cms.omniupdate.com/authentication/login",
      {
        method: "POST",
        body,
      },
    );
    return response.json();
  };

  const connection = await connect();
  const token = connection.gadget_token;
  // console.log(`#######\n Got Token: ${token} \n#######`);

  const calPending =
    "https://a.cms.omniupdate.com/rs/calendars/www/reports/pending-approvals?category=&category=General&timezone=US%2FEastern";

  const components =
    "https://a.cms.omniupdate.com/rs/components/generic/C20%20-%20Accordion";

  const componentList = await fetch(components, {
    method: "GET",
    headers: {
      "X-Auth-Token": token,
    },
  });

  const componentData = await componentList.json();
  console.log(componentData);

  // const groupList = await fetch(calPending, {
  //   method: "GET",
  //   headers: {
  //     "X-Auth-Token": token,
  //   },
  // });
  // const groupData = await groupList.json();
  // console.log(groupData[0]);
})();
