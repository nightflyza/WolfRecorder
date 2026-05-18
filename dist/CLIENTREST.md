# Client REST API

HTTP JSON API for WolfRecorder client apps (mobile, desktop, embedded players). Unlike the admin **RestAPI** (`action=rest` + instance serial `key`), this endpoint has **no serial key** but **authenticates a WolfRecorder user on every call** and returns only channels allowed by ACL.

| | |
|---|---|
| Implementation | `api/libs/api.clientrestapi.php` |
| Entry point | `modules/general/remoteapi/index.php` → `action=clientrest` |
| Auth module | `remoteapi` is in `YALF_NO_AUTH_MODULES` — no web UI session required |

---

## Base URL and sample credentials

```
http://somehost.com/wr/
```

| Field | Example |
|-------|---------|
| login | `admin` |
| password | `demo` |

Use the same path prefix as your WolfRecorder web install (often `/wr/`).

---

## Request shape

```
GET  {base}?module=remoteapi&action=clientrest&{object}={method}
POST application/x-www-form-urlencoded: data=<JSON string>
```

### Method selection (GET query)

| GET param | Value | Handler |
|-----------|-------|---------|
| `channels` | `getall` | List channels for the authenticated user (with runtime state and screenshots) |

Full example URL:

```
http://somehost.com/wr/?module=remoteapi&action=clientrest&channels=getall
```

### Body (POST field `data`)

JSON object merged with optional GET auth params (GET is intended for browser/curl debugging only).

| Field | Required | Description |
|-------|----------|-------------|
| `login` | yes | WolfRecorder username |
| `password` | one of | Plain password; server compares `md5(password)` to stored hash |
| `authtoken` | one of | `md5(login + stored_password_hash)` — reuse after first login without sending password |

Optional GET aliases: `login`, `password`, `authtoken` on the query string.

---

## Examples

### channels/getall (password)

```bash
curl -s -X POST \
  'http://somehost.com/wr/?module=remoteapi&action=clientrest&channels=getall' \
  --data-urlencode 'data={"login":"admin","password":"demo"}'
```

### channels/getall (authtoken)

After you know the user’s stored password hash from the DB (`userData['password']`):

```
authtoken = md5(login + stored_password_hash)
```

```bash
curl -s -X POST \
  'http://somehost.com/wr/?module=remoteapi&action=clientrest&channels=getall' \
  --data-urlencode 'data={"login":"admin","authtoken":"<computed>"}'
```

### Browser-style GET (debug only)

```
http://somehost.com/wr/?module=remoteapi&action=clientrest&channels=getall&login=admin&password=demo
```

Prefer POST + JSON `data` in production clients.

---

## Response format

Always `Content-Type: application/json; charset=UTF-8`. Top-level `error`: **0 = success**.

### channels/getall — success

```json
{
  "error": 0,
  "channels": [
    {
      "id": "CH001",
      "comment": "Front door",
      "active": 1,
      "recording": 1,
      "mainstream": 1,
      "substream": 0,
      "screenshot": "content/chanshots/CH001.jpg"
    }
  ]
}
```

| Channel field | Type | Meaning |
|---------------|------|---------|
| `id` | string | Channel ID |
| `comment` | string | Camera description |
| `active` | 0 \| 1 | Camera enabled |
| `recording` | 0 \| 1 | Recorder process running |
| `mainstream` | 0 \| 1 | Main live stream running |
| `substream` | 0 \| 1 | Sub live stream running |
| `screenshot` | string | Relative path to preview image or placeholder |

**Preview URL:** `{base}{screenshot}` (no extra slash if `base` ends with `/` and path does not start with `/`).

Placeholder `screenshot` values (`ChanShots` constants):

| Value | Meaning |
|-------|---------|
| `skins/nosignal.gif` | No signal |
| `skins/error.gif` | Corrupt or invalid snapshot |
| `skins/chanblock.gif` | Camera disabled |

---

## Live HLS streams (pseudostream)

Use the channel `id` from `channels/getall` to build HLS master playlist URLs. The **pseudostream** module (`?module=pseudostream`) reads the on-disk playlist, rewrites segment paths to web-relative URLs, and returns `application/vnd.apple.mpegurl` body. No WolfRecorder login is required for this module (it is listed in `YALF_NO_AUTH_MODULES`).

| Stream | Quality | GET parameter | On-disk HLS dir | Typical use |
|--------|---------|---------------|-----------------|-------------|
| Main | High | `live` | `howl/livestreams/{channelId}/` | Full-screen live view |
| Sub | Low / fast | `sub` | `howl/livelq/{channelId}/` | Grids, previews, low bandwidth |

Check `mainstream` / `substream` in `channels/getall`: value `1` means the ffmpeg process is already running; `0` means it is not. The first request to a pseudostream URL can still start capture via background `liveswarm` / `subswarm` (may take a few seconds before segments appear).

### URL templates

Replace `{base}` with your install root (e.g. `http://somehost.com/wr/`) and `{channelId}` with `channels[].id` (e.g. `CH001`).

**Main (high quality):**

```
{base}?module=pseudostream&live={channelId}
```

**Sub (low quality):**

```
{base}?module=pseudostream&sub={channelId}
```

### Examples for channel `CH001`

| Stream | Full playlist URL |
|--------|-------------------|
| Main | `http://somehost.com/wr/?module=pseudostream&live=CH001` |
| Sub | `http://somehost.com/wr/?module=pseudostream&sub=CH001` |

Pass this URL directly to an HLS player (`AVPlayer`, ExoPlayer, hls.js, VLC, etc.) as the media source. The player will fetch the playlist from pseudostream, then request segments under `{base}howl/...`.

### Sample playlist response

Request (main):

```http
GET /wr/?module=pseudostream&live=CH001 HTTP/1.1
Host: somehost.com
```

Response body (illustrative; segment names vary):

```m3u8
#EXTM3U
#EXT-X-VERSION:3
#EXT-X-TARGETDURATION:2
#EXTINF:2.000,
howl/livestreams/CH001/segment00001.ts
#EXTINF:2.000,
howl/livestreams/CH001/segment00002.ts
```

Sub stream uses the same shape with `howl/livelq/CH001/` instead of `howl/livestreams/CH001/`.

**Segment URL:** `{base}` + line from playlist (e.g. `http://somehost.com/wr/howl/livestreams/CH001/segment00001.ts`).

### Client flow

1. `channels/getall` → read `id`, `mainstream`, `substream`, `active`.
2. If `active === 0`, do not expect a live picture; show `screenshot` or a placeholder.
3. Choose URL:
   - grid / thumbnail wall → `?module=pseudostream&sub={id}`
   - single full-quality live → `?module=pseudostream&live={id}`
4. Open the URL in your HLS stack; refresh the playlist on the player’s HLS interval.
5. Optional: poll `channels/getall` to reflect when `mainstream` / `substream` flip to `1` after cold start.

Implementation reference: `modules/general/pseudostream/index.php`, `LiveCams::getPseudoStream()` / `getPseudoSubStream()` in `api/libs/api.livecams.php`.

### Error codes

| `error` | When |
|---------|------|
| 0 | Success |
| 1 | No API object in GET (missing e.g. `channels=...`) |
| 2 | Unknown method for object |
| 3 | Missing required fields (e.g. `login`) |
| 6 | Wrong login, password, or authtoken |
| 7 | User profile empty or unreadable |

Example:

```json
{"error":6,"message":"Wrong credentials"}
```

---

## ACL

- Users with **OPERATOR** or **ROOT** rights receive all channels.
- Other users receive only channels assigned in ACL for that `login`.

---

## Client REST vs Admin REST

| | Client (`clientrest`) | Admin (`rest` + `key`) |
|--|----------------------|-------------------------|
| URL | `?module=remoteapi&action=clientrest&...` | `?module=remoteapi&key=<serial>&action=rest&...` |
| Auth | Per-request `login` + `password` or `authtoken` | Instance serial key |
| Scope | User ACL, enriched channel list | Full management API (cameras, users, ACL CRUD, etc.) |

Admin API lives in `api/libs/api.restapi.php` and is loaded via `modules/remoteapi/rest.php` when `key` matches the instance serial.

---

## Client integration checklist

1. Set base URL to your install root (e.g. `http://somehost.com/wr/`).
2. POST `data` as URL-encoded JSON with `login` and `password` (or cached `authtoken`).
3. GET query: `module=remoteapi`, `action=clientrest`, plus object/method (e.g. `channels=getall`).
4. Require `error === 0`; read `channels` array.
5. Build preview URLs as `{base} + channel.screenshot`.
6. Build live HLS URLs from `channel.id`: main → `?module=pseudostream&live={id}`, sub → `?module=pseudostream&sub={id}` (see [Live HLS streams](#live-hls-streams-pseudostream)).
7. Optionally persist `authtoken = md5(login + stored_hash)` after first successful auth.

---

## Extending the API

New operations are registered in `ClientRestAPI::setAvailableObjects()`. Current surface:

- `channels` → `getall` only

Pattern matches `RestAPI`: GET `{object}={method}`, POST JSON in field `data`, handler returns an array encoded as JSON.
