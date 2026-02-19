<?php

namespace App\Events;

use App\Models\Emote;
use App\Models\User;
use Discord\Discord;
use Discord\Parts\WebSockets\MessageReaction;
use Discord\WebSockets\Event as Events;
use Laracord\Events\Event;

class MessageReactionRemove extends Event
{
    /**
     * The event handler.
     *
     * @var string
     */
    protected $handler = Events::MESSAGE_REACTION_REMOVE;

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

        $user = User::where('discord_id', $reaction->user_id)->first();

        if (! $user) {
            return;
        }

        $emoteId = $reaction->emoji->id ?? $reaction->emoji->name;
        $emote = Emote::where('emote_id', $emoteId)
            ->where('guild_id', $reaction->guild_id)
            ->first();

        if ($emote) {
            $emote->removeReaction(
                $user,
                $reaction->guild_id,
                $reaction->message_id,
                boolval($reaction->emoji->id) ? null : $reaction->emoji->name
            );
        }
    }
}
