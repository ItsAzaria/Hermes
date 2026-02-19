<?php

namespace App\Events;

use App\Models\EmoteGuildStat;
use App\Models\EmoteLog;
use Discord\Discord;
use Discord\Parts\WebSockets\MessageReaction;
use Discord\WebSockets\Event as Events;
use Laracord\Events\Event;

class MessageReactionRemoveAll extends Event
{
    /**
     * The event handler.
     *
     * @var string
     */
    protected $handler = Events::MESSAGE_REACTION_REMOVE_ALL;

    /**
     * Handle the event.
     */
    public function handle(MessageReaction $reaction, Discord $discord)
    {
        if ($reaction->user->bot || ! $reaction->guild_id) {
            return;
        }

        if ($reaction->emoji && $reaction->emoji->id) {

            if ($reaction->emoji->guild_id) {
                if ($reaction->emoji->guild_id !== $reaction->guild_id) {
                    return;
                }
            } else {
                $guild = $discord->guilds->get('id', $reaction->guild_id);
                if (! $guild || ! $guild->emojis->get('id', $reaction->emoji->id)) {
                    return;
                }
            }
        }

        $logs = EmoteLog::where('message_id', $reaction->message->id)
            ->where('guild_id', $reaction->message->guild_id)
            ->get();

        foreach ($logs as $log) {
            EmoteGuildStat::where([
                'emote_id' => $log->emote_id,
                'guild_id' => $log->guild_id,
            ])->decrement('usage_count');
        }

        EmoteLog::where('message_id', $reaction->message->id)
            ->where('guild_id', $reaction->message->guild_id)
            ->delete();
    }
}
