-- #!mysql

-- #{ table
    -- #{ cooldowns
        CREATE TABLE IF NOT EXISTS cooldowns (
            uuid VARCHAR(36) NOT NULL,
            kit VARCHAR(64) NOT NULL,
            cooldown INT NOT NULL,
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
        ON DUPLICATE KEY UPDATE cooldown = VALUES(cooldown);
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