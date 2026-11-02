// Serves the static site and the MCP server at /mcp (src/mcp.js). www gets a
// permanent redirect to the same path on distinctui.com. try.distinctui.com is the
// distinctUI+ demo (site/try): WordPress Playground fetches its zips cross-origin.
// Other hosts (previews) are served as is.
import { handleMCP } from "./mcp.js";

const HOME = "distinctui.com";
const MOVED = new Set(["www.distinctui.com"]);
const TRY = "try.distinctui.com";

export default {
  async fetch(request, env) {
    const url = new URL(request.url);
    if (MOVED.has(url.hostname)) {
      url.hostname = HOME;
      url.protocol = "https:";
      url.port = "";
      return Response.redirect(url.toString(), 301);
    }
    if (url.pathname === "/mcp" || url.pathname === "/mcp/") return handleMCP(request, env);
    if (url.hostname === TRY && url.pathname === "/") {
      url.pathname = "/try/";
      return env.ASSETS.fetch(new Request(url, request));
    }
    if (url.pathname.startsWith("/try/") && url.pathname.endsWith(".zip")) {
      if (request.method === "OPTIONS") return new Response(null, { status: 204, headers: { "Access-Control-Allow-Origin": "*", "Access-Control-Allow-Methods": "GET, HEAD, OPTIONS" } });
      const res = await env.ASSETS.fetch(request);
      const out = new Response(res.body, res);
      out.headers.set("Access-Control-Allow-Origin", "*");
      return out;
    }
    return env.ASSETS.fetch(request);
  },
};
