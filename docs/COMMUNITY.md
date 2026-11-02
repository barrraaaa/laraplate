# Community & leaderboard

The community channels are template-wired, not a managed service:

- **Discord**: set `DISCORD_INVITE_URL` in `.env` — the landing footer
  shows a "Join the community" button when it is set.
- **Leaderboard**: set `LEADERBOARD_URL` to your own leaderboard (a
  Notion page, a product-hunt-style wall, or a small admin page). A
  friend-leaderboard for "who shipped what" is planned for the V1.2
  branch of the buyer community section of this doc.

## Operating the community (seller playbook)

1. Create the Discord server, welcome channel + #changelog with
   post-on-new-commit automation (any webhook bot).
2. Drop every release note into #changelog — it doubles as the lifetime-
   updates delivery channel.
3. Run a monthly "ship it" thread; the leaderboard rewards shipped
   products built on the boilerplate (revenue share shoutouts work well).
4. Pin the deal-code message in the All-in channel only.