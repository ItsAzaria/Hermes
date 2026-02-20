<?php

namespace App\Services;

use Laracord\Services\Service;

class UpdateEmotes extends Service
{
    /**
     * The service interval.
     */
    protected int $interval = 600; // 10 minutes

    /**
     * Handle the service.
     */
    public function handle(): void
    {
        $guildId = env('GUILD_ID');
        $guild = $this->discord->guilds->get('id', $guildId);
        if (! $guild) {
            return;
        }

        foreach ($guild->emojis as $emoji) {
            $emote = \App\Models\Emote::where('emote_id', $emoji->id)->first();

            if (! $emote) {
                $emoteModel = \App\Models\Emote::create([
                    'emote_id' => $emoji->id,
                    'guild_id' => $guildId,
                    'emote_name' => $emoji->name,
                    'type' => $emoji->animated ? 'ANIMATED' : 'STATIC',
                ]);

                \App\Models\EmoteGuildStat::firstOrCreate(
                    ['emote_id' => $emoteModel->emote_id, 'guild_id' => $guildId],
                    ['usage_count' => 0]
                );
            } elseif ($emote->emote_name !== $emoji->name) {
                $emote->emote_name = $emoji->name;
                $emote->save();
            }
        }
    }
}
