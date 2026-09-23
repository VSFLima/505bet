const t = {
  cache: "no-cache",
  headers: {},
  redirect: "follow",
  mode: "cors",
};
function e(e, s = "POST", a, n, i = "json") {
  var o;
  const c = n || {};
  const r = Object.assign(t, { method: s });
  Object.keys(t.headers || {}).length > 0 && (r.headers = t.headers);
  const l = Object.assign(r, c);
  if (
    ((l.method =
      null === (o = l.method) || void 0 === o ? void 0 : o.toUpperCase()),
      "GET" === l.method)
  ) {
    if (a) {
      let t = "";
      const s = /([?&])_=[^&]*/,
        n = /\?/;
      "object" == typeof a &&
        (t = (function (t) {
          let e = "";
          for (const s in t)
            t.hasOwnProperty(s) &&
              (e += s + "=" + encodeURIComponent(t[s]) + "&");
          return e.slice(0, -1);
        })(a)),
        (e = s.test(e)
          ? e.replace(s, "$1" + t)
          : e + (n.test(e) ? "&" : "?") + t);
    }
  } else
    [FormData, ArrayBuffer, Blob].filter((t) => a instanceof t).length > 0
      ? (l.body = a)
      : ((l.headers = Object.assign(
        { "Content-Type": "application/json" },
        l.headers
      )),
        a && (l.body = "string" == typeof a ? a : JSON.stringify(a)));
  return fetch(e, l)
    .then((t) => {
      const { status: e } = t;
      if (e >= 200 && e < 400) {
        let e;
        switch (i) {
          case "json":
            e = new Promise((e) => {
              t.text().then((t) => {
                try {
                  e(JSON.parse(t));
                } catch (s) {
                  e(t);
                }
              });
            });
            break;
          case "text":
            e = t.text();
            break;
          case "blob":
            e = t.blob();
            break;
          case "arraybuffer":
            e = t.arrayBuffer();
            break;
          default:
            e = t.json();
        }
        return e;
      }
      return Promise.reject(t);
    })
    .catch((t) => Promise.reject(t));
}
const s = (t, s, a, n) => e(t, "POST", s, a, n);
function a(t, e, a = "W3Push") {
  t.forEach((t) => {
    const e = (new Date().getTime() / 1e3) | 0;
    (t.itime = e), t.msg_id && (t.msg_id = t.msg_id.toString());
  });
  const n = {
    platform: "b",
    uid: e.engagelab_uid,
    app_key: e.engagelab_appkey,
    channel: a,
    content: t,
  };
  s("https://webpushstat.api.engagelab.cc/v4/web/report", n, {
    headers: {
      Authorization: "Basic " + btoa(n.uid + ":" + e.engagelab_passwd),
    },
  });
}