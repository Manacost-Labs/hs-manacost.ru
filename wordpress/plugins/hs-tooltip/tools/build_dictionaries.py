#!/usr/bin/env python3
"""Rebuild hs_dictionary.json and hs_bgs_dictionary.json from HearthstoneJSON.

Output format — same as the plugin expects:

    hs_dictionary.json:
        {
          "by_id":   { "<CARD_ID>": { "img", "rarity", "name", "set_icon" } },
          "by_name": { "<lower-case name form>": { "img", "rarity", "name", "set_icon" } }
        }

    hs_bgs_dictionary.json:
        {
          "by_id":   { "<BG_ID>":   { "img", "rarity", "name", "type", "techLevel" } }
        }

`by_name` keys include every morphological form generated via pymorphy3
(nominative + all inflected forms) so the plugin can match any grammatical
case in text. If pymorphy3 is not installed, only the nominative form is
used — tooltips will still work via shortcodes / exact match.

Usage:
    pip install requests pymorphy3 pymorphy3-dicts-ru
    python tools/build_dictionaries.py                 # rebuild both
    python tools/build_dictionaries.py --only main     # main dictionary only
    python tools/build_dictionaries.py --only bgs      # BG dictionary only
    python tools/build_dictionaries.py --pretty        # pretty-print JSON
"""

from __future__ import annotations

import argparse
import json
import re
import sys
from pathlib import Path
from typing import Any, Iterable

try:
    import requests
except ImportError:
    sys.exit("Missing dependency: pip install requests")

try:
    import pymorphy3  # type: ignore

    _MORPH = pymorphy3.MorphAnalyzer()
    HAS_MORPH = True
except Exception:
    _MORPH = None
    HAS_MORPH = False


API_URL = "https://api.hearthstonejson.com/v1/latest/ruRU/cards.json"
ART_BG = "https://art.hearthstonejson.com/v1/bgs/latest/ruRU/256x/{id}.png"
# CloudFront host used by the existing dictionary for collectible art.
ART_RENDER = "https://d15f34w2p8l1cc.cloudfront.net/hearthstone/{hash}.png"

RARITY_MAP = {
    "FREE": "common",
    "COMMON": "common",
    "RARE": "rare",
    "EPIC": "epic",
    "LEGENDARY": "legendary",
}

BG_KEEP_TYPES = {
    "MINION",
    "HERO",
    "SPELL",
    "BATTLEGROUND_SPELL",
    "BATTLEGROUND_ANOMALY",
    "BATTLEGROUND_TRINKET",
    "BATTLEGROUND_QUEST_REWARD",
}

BG_TECH_TO_RARITY = {
    1: "common",
    2: "common",
    3: "rare",
    4: "rare",
    5: "epic",
    6: "legendary",
    7: "legendary",
}

# Set id → SVG filename in set_icon/ (extend as needed when a new set ships).
SET_ICON_MAP: dict[str, str] = {
    "CORE": "",
    "EXPERT1": "",
    "VANILLA": "",
    "HOF": "Hall_of_Fame_-_SVG_logo.svg",
    "NAXX": "",
    "GVG": "Goblins_vs_Gnomes_-_SVG_logo.svg",
    "BRM": "",
    "TGT": "The_Grand_Tournament_-_SVG_logo.svg",
    "LOE": "",
    "OG": "Whispers_of_the_Old_Gods_-_SVG_logo.svg",
    "KARA": "",
    "GANGS": "Mean_Streets_of_Gadgetzan_-_SVG_logo.svg",
    "UNGORO": "Journey_to_Un'Goro_-_SVG_logo.svg",
    "ICECROWN": "Knights_of_the_Frozen_Throne_-_SVG_logo.svg",
    "LOOTAPALOOZA": "Kobolds_and_Catacombs_-_SVG_logo.svg",
    "GILNEAS": "The_Witchwood_-_SVG_logo.svg",
    "BOOMSDAY": "The_Boomsday_Project_-_SVG_logo.svg",
    "TROLL": "Rastakhan's_Rumble_-_SVG_logo.svg",
    "DALARAN": "Rise_of_Shadows_-_SVG_logo.svg",
    "ULDUM": "Saviors_of_Uldum_-_SVG_logo.svg",
    "DRAGONS": "Descent_of_Dragons_-_SVG_logo.svg",
    "YEAR_OF_THE_DRAGON": "",
    "BLACK_TEMPLE": "Ashes_of_Outland_-_SVG_logo.svg",
    "SCHOLOMANCE": "Scholomance_Academy_-_SVG_logo.svg",
    "DARKMOON_FAIRE": "Madness_at_the_Darkmoon_Faire_-_SVG_logo.svg",
    "THE_BARRENS": "Forged_in_the_Barrens_-_SVG_logo.svg",
    "STORMWIND": "United_in_Stormwind_-_SVG_logo.svg",
    "ALTERAC_VALLEY": "Fractured_in_Alterac_Valley_-_SVG_logo.svg",
    "THE_SUNKEN_CITY": "Voyage_to_the_Sunken_City_-_SVG_logo.svg",
    "REVENDRETH": "Murder_at_Castle_Nathria_-_SVG_logo.svg",
    "PATH_OF_ARTHAS": "Path_of_Arthas_-_SVG_logo.svg",
    "RETURN_OF_THE_LICH_KING": "March_of_the_Lich_King_-_SVG_logo.svg",
    "BATTLE_OF_THE_BANDS": "Festival_of_Legends_-_SVG_logo.svg",
    "TITANS": "TITANS_-_SVG_logo.svg",
    "WILD_WEST": "Showdown_in_the_Badlands_-_SVG_logo.svg",
    "WHIZBANGS_WORKSHOP": "Whizbang's_Workshop_-_SVG_logo.svg",
    "ISLAND_VACATION": "Perils_in_Paradise_-_SVG_logo.svg",
    "SPACE": "The_Great_Dark_Beyond_-_SVG_logo.svg",
    "EMERALD_DREAM": "Into_the_Emerald_Dream_-_SVG_logo.svg",
    "ESCAPEFROM_VIOLET_HOLD": "Escape_from_Violet_Hold_-_SVG_logo.svg",
    "EVENT": "Event_-_SVG_logo.svg",
    "WONDERS": "Caverns_of_Time_-_SVG_logo.svg",
    "PLACEHOLDER_202204": "Across_the_Timeways_-_SVG_logo.svg",
    "DEMON_HUNTER_INITIATE": "Demon_Hunter_Initiate_-_SVG_logo.svg",
    "NATHRIA": "Murder_at_Castle_Nathria_-_SVG_logo.svg",
}


def fetch_cards() -> list[dict[str, Any]]:
    print(f"[1/4] GET {API_URL}")
    resp = requests.get(API_URL, timeout=60)
    resp.raise_for_status()
    cards = resp.json()
    if not isinstance(cards, list):
        raise RuntimeError("Unexpected API payload: not a list")
    print(f"      {len(cards)} cards total")
    return cards


def card_image_url(card: dict[str, Any]) -> str:
    """Derive a stable CloudFront URL for a collectible card render.

    HearthstoneJSON doesn't expose the asset hash, but the plugin's
    existing dictionary uses these CloudFront URLs directly. When
    regenerating we fall back to the HearthstoneJSON render endpoint,
    which is the official cache-friendly path.
    """
    cid = card.get("id", "")
    return f"https://art.hearthstonejson.com/v1/render/latest/ruRU/256x/{cid}.png"


_word_re = re.compile(r"[\w\-]+", re.UNICODE)


def generate_forms(name: str) -> list[str]:
    """Return all lowercase morphological forms of a Russian card name.

    Each *word* inside the name is inflected independently; results are
    cross-joined and de-duplicated. If pymorphy3 isn't available, only
    the lowercase original is returned.
    """
    name_lc = name.lower()
    if not HAS_MORPH or not name_lc.strip():
        return [name_lc]

    # Split on word-like tokens, keep separators to reassemble.
    tokens = re.split(r"(\W+)", name_lc, flags=re.UNICODE)
    word_positions = [i for i, t in enumerate(tokens) if _word_re.fullmatch(t or "")]
    if not word_positions:
        return [name_lc]

    # For each word: collect unique inflections.
    per_word: list[list[str]] = []
    for idx in word_positions:
        w = tokens[idx]
        parses = _MORPH.parse(w) if _MORPH else []
        if not parses:
            per_word.append([w])
            continue
        best = parses[0]
        forms = {w}
        try:
            for lex in best.lexeme:
                forms.add(lex.word)
        except Exception:
            pass
        per_word.append(sorted(forms))

    # Cap combinatorial explosion.
    cap = 1
    for group in per_word:
        cap *= max(1, len(group))
        if cap > 5000:
            # Too many combinations — keep only the nominative to stay sane.
            return [name_lc]

    # Cross-join.
    results: list[list[str]] = [[]]
    for idx_in_words, idx_in_tokens in enumerate(word_positions):
        new_results: list[list[str]] = []
        for prefix in results:
            for form in per_word[idx_in_words]:
                new_results.append(prefix + [form])
        results = new_results

    out: set[str] = set()
    for combo in results:
        copy = list(tokens)
        for slot, idx in enumerate(word_positions):
            copy[idx] = combo[slot]
        out.add("".join(copy))
    return sorted(out)


def build_main(cards: list[dict[str, Any]]) -> dict[str, Any]:
    print("[2/4] Building main dictionary (collectible cards)...")
    by_id: dict[str, dict[str, Any]] = {}
    by_name: dict[str, dict[str, Any]] = {}
    collectible = [
        c for c in cards
        if c.get("collectible") and c.get("set") != "BATTLEGROUNDS"
        and c.get("type") in {"MINION", "SPELL", "WEAPON", "HERO", "LOCATION"}
        and isinstance(c.get("name"), str) and isinstance(c.get("id"), str)
    ]
    print(f"      {len(collectible)} collectible cards")

    for c in collectible:
        cid = c["id"]
        name = c["name"]
        rarity = RARITY_MAP.get((c.get("rarity") or "COMMON").upper(), "common")
        set_icon = SET_ICON_MAP.get(c.get("set", ""), "")
        entry = {
            "img": card_image_url(c),
            "rarity": rarity,
            "name": name,
            "set_icon": set_icon,
        }
        by_id[cid] = entry

        for form in generate_forms(name):
            # First writer wins to avoid thrash; duplicates across cards are rare
            # and not worth the complexity of resolving.
            by_name.setdefault(form, entry)

    print(f"      by_id: {len(by_id)}, by_name forms: {len(by_name)}"
          f" (morph={'pymorphy3' if HAS_MORPH else 'disabled'})")
    return {"by_id": by_id, "by_name": by_name}


def build_bgs(cards: list[dict[str, Any]]) -> dict[str, Any]:
    print("[3/4] Building BG dictionary...")
    by_id: dict[str, dict[str, Any]] = {}
    bg = [
        c for c in cards
        if c.get("set") == "BATTLEGROUNDS"
        and c.get("type") in BG_KEEP_TYPES
        and isinstance(c.get("name"), str) and isinstance(c.get("id"), str)
    ]
    for c in bg:
        cid = c["id"]
        tech = int(c.get("techLevel") or 0)
        rarity = BG_TECH_TO_RARITY.get(tech, "common")
        by_id[cid] = {
            "img": ART_BG.format(id=cid),
            "rarity": rarity,
            "name": c["name"],
            "type": c.get("type", ""),
            "techLevel": tech,
        }
    print(f"      {len(by_id)} BG entries")
    return {"by_id": by_id}


def dump(path: Path, data: dict[str, Any], pretty: bool) -> None:
    print(f"[4/4] Writing {path} ...")
    if pretty:
        text = json.dumps(data, ensure_ascii=False, indent=2)
    else:
        text = json.dumps(data, ensure_ascii=False, separators=(",", ":"))
    path.write_text(text, encoding="utf-8")
    print(f"      {path.stat().st_size / 1024:.1f} KB")


def main() -> int:
    ap = argparse.ArgumentParser(description=__doc__)
    ap.add_argument("--only", choices=["main", "bgs"], help="Build only one dictionary")
    ap.add_argument("--pretty", action="store_true", help="Pretty-print JSON")
    ap.add_argument("--out-dir", default=".", help="Directory for output files")
    args = ap.parse_args()

    out_dir = Path(args.out_dir).resolve()
    out_dir.mkdir(parents=True, exist_ok=True)

    cards = fetch_cards()

    if args.only in (None, "main"):
        main_dict = build_main(cards)
        dump(out_dir / "hs_dictionary.json", main_dict, args.pretty)

    if args.only in (None, "bgs"):
        bgs_dict = build_bgs(cards)
        dump(out_dir / "hs_bgs_dictionary.json", bgs_dict, args.pretty)

    if not HAS_MORPH and args.only != "bgs":
        print()
        print("  ⚠  pymorphy3 is not installed — by_name contains only the")
        print("     nominative form. Install it for full declension support:")
        print("         pip install pymorphy3 pymorphy3-dicts-ru")

    return 0


if __name__ == "__main__":
    sys.exit(main())
