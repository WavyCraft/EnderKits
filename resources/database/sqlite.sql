-- #!sqlite

-- #{ table
    -- #{ cooldowns
        CREATE TABLE IF NOT EXISTS cooldowns (
            uuid TEXT NOT NULL,
            kit TEXT NOT NULL,
            cooldown INTEGER NOT NULL,
            PRIMARY KEY (uuid, kit)
        );
    -- #}
-- #}

-- #{ cooldowns
    -- #{ set
        -- # :uuid string
        -- # :kit string
        -- # :cooldown int
        INSERT INTO cooldowns (uuid, kit, cooldown)
        VALUES (:uuid, :kit, :cooldown)
        ON CONFLICT(uuid, kit) DO UPDATE SET cooldown = excluded.cooldown;
    -- #}

    -- #{ get
        -- # :uuid string
        -- # :kit string
        SELECT cooldown FROM cooldowns WHERE uuid = :uuid AND kit = :kit;
    -- #}

    -- #{ remove
        -- # :uuid string
        -- # :kit string
        DELETE FROM cooldowns WHERE uuid = :uuid AND kit = :kit;
    -- #}

    -- #{ cleanup
        -- # :time int
        DELETE FROM cooldowns WHERE cooldown <= :time;
    -- #}
-- #}