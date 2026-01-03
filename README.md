# Cabin Analytics (WordPress)

Provides:
- Dashboard widget (range tabs + refresh)
- Dynamic block: **Cabin Analytics**
- Shortcode: `[cabin_analytics]`

## Setup
1. Install and activate the plugin.
2. Go to **Settings → Cabin Analytics** and set your Cabin API key.
3. Set your default Mode and Range.

## Shortcode
`[cabin_analytics mode="chart" range="14d" show_footer="1"]`

Attributes:
- `mode`: `chart` or `sparkline` (optional)
- `range`: `7d`, `14d`, `30d` (optional)
- `domain`: override domain (optional)
- `show_footer`: `1` or `0`

## Notes
Popover positioning uses modern browser APIs. If unsupported, the chart still renders; popovers may not appear.

## License

GPL-2.0-or-later  
© 2026 Stephen Walker

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.