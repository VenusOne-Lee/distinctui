// Serves the static site. www gets a permanent redirect to the same path on
// distinctui.com. Other hosts (previews) are served as is.
const HOME = "distinctui.com";
const MOVED = new Set(["www.distinctui.com"]);

export default {
  async fetch(request, env) {
    const url = new URL(request.url);
    if (MOVED.has(url.hostname)) {
      url.hostname = HOME;
      url.protocol = "https:";
      url.port = "";
      return Response.redirect(url.toString(), 301);
    }
    return env.ASSETS.fetch(request);
  },
};
