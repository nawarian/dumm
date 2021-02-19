DUMM
---

A php port of the 1993's DOOM based on [Amro Ibrahim's DIYDoom](https://github.com/amroibrahim/DIYDoom).

# Requirements

- PHP 7.4
- [Joseph Montanez's raylib extension](https://github.com/joseph-montanez/raylib-php)
- [Basic knowledge on how to use raylib](https://thephp.website/en/issue/games-with-php/)

# Running the project

Make sure you've placed `DOOM1.wad` and `raylib.so` in the root path.

```
$ ./doom
```

# Current State
![Rendering automap + root node](current-state.png)

The screen shows a default spacing taken for Ceiling and Floor.

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
- [ ] Render scene rectangles
- [ ] Add perspective
- [ ] Draw floor and ceiling
- [ ] Draw textures
- [ ] Move around

