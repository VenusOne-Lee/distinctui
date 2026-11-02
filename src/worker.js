// Serves the static site and the MCP server at /mcp (src/mcp.js). www gets a
// permanent redirect to the same path on distinctui.com. Other hosts (previews)
// are served as is.
import { handleMCP } from "./mcp.js";

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
    if (url.pathname === "/mcp" || url.pathname === "/mcp/") return handleMCP(request, env);
    return env.ASSETS.fetch(request);
  },
};
