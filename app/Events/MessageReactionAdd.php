<?php

namespace App\Events;

use App\Models\Emote;
use App\Models\User;
use Discord\Discord;
use Discord\Parts\WebSockets\MessageReaction;
use Discord\WebSockets\Event as Events;
use Laracord\Events\Event;

class MessageReactionAdd extends Event
{
    /**
     * The event handler.
     *
     * @var string
     */
    protected $handler = Events::MESSAGE_REACTION_ADD;

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

        $user = User::firstOrCreate(
            ['discord_id' => $reaction->user_id],
            ['username' => $reaction->user->username]
        );

        $emoteId = $reaction->emoji->id ?? $reaction->emoji->name;
        $type = $reaction->emoji->id ? ($reaction->emoji->animated ? 'ANIMATED' : 'STATIC') : 'UNICODE';
        $guildId = $reaction->guild_id;

        $emote = Emote::firstOrCreate(
            ['emote_id' => $emoteId],
            [
                'guild_id' => $guildId,
                'emote_name' => $reaction->emoji->name,
                'type' => $type,
            ]
        );

        $emote->addReaction(
            user: $user,
            guildId: $reaction->guild_id,
            channelId: $reaction->channel_id,
            messageId: $reaction->message_id,
            unicodeEmoji: boolval($reaction->emoji->id) ? null : $reaction->emoji->name
        );

    }
}
