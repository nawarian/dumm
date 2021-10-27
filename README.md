DUMM
---

A php port of the 1993's DOOM based on [Amro Ibrahim's DIYDoom](https://github.com/amroibrahim/DIYDoom).

# Requirements

- PHP 8.0
- [Basic knowledge on how to use raylib](https://thephp.website/en/issue/games-with-php/)
- [Raylib](https://raylib.com)

# Running the project

Make sure you've placed `DOOM1.wad` in the root path.

```
$ ./doom
```

# Current State
![Rendering automap + root node](dumm-demo.gif)

The above demo shows how 2D and 3D rendering of the map currently works.

Textures aren't yet being applied.

Every solid object has a texture (from WAD definitions) and
such definitions set a texture name. For each texture name
I adopted a random color and cached it. That's how the two columns
appear with the same colors on the screen.

# Roadmap

- [x] Read WAD file
- [x] Render automap
- [x] Traverse BSP tree
- [x] Render scene lines
- [x] Clip solid walls
- [x] Render scene rectangles
- [x] Add perspective
- [ ] Draw floor and ceiling
- [ ] Draw textures
- [ ] Move around

