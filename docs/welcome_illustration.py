"""Draws the Welcome illustration (mobile/assets/welcome.png): a bin with leaves, a plastic bottle and a
newspaper around it, flat style, transparent background.

It is shown on the teal (#0F766E) blocks of the Welcome screen and the Beranda banner, so the shapes use
white and light teal with dark teal details; teal shapes would disappear into that background.

Needs Pillow, which the training venv already has:

    ml\\.venv\\Scripts\\python docs\\welcome_illustration.py
"""
import math
from pathlib import Path

from PIL import Image, ImageDraw

OUTPUT = Path(__file__).resolve().parent.parent / "mobile" / "assets" / "welcome.png"

WIDTH, HEIGHT = 800, 560
# Drawn at 4x and scaled down at the end: ImageDraw does not anti-alias edges.
SCALE = 4

TEAL = "#0F766E"
TEAL_DARK = "#115E59"
TEAL_LIGHT = "#CCFBF1"
WHITE = "#FFFFFF"
GRAY300 = "#D1D5DC"
GRAY500 = "#6A7282"


def s(*values):
    """Scales design coordinates (800 x 560 canvas) to the drawing canvas."""
    return [round(v * SCALE) for v in values]


def paste_rotated(canvas, layer, center, angle):
    """Rotates a separately drawn shape and places its center at `center` (design coordinates)."""
    rotated = layer.rotate(angle, resample=Image.BICUBIC, expand=True)
    x, y = s(*center)
    canvas.alpha_composite(rotated, (x - rotated.width // 2, y - rotated.height // 2))


def leaf(length, width, color, vein):
    stalk = length * 0.18
    layer = Image.new("RGBA", tuple(s(length + stalk, width)), (0, 0, 0, 0))
    draw = ImageDraw.Draw(layer)
    middle = width / 2
    draw.line(s(0, middle, stalk + 2, middle), fill=color, width=s(4)[0])
    # Pointed at both ends: the half-width follows a sine curve along the length.
    steps = 40
    top = [(stalk + length * i / steps, middle - middle * math.sin(math.pi * i / steps)) for i in range(steps + 1)]
    bottom = [(x, 2 * middle - y) for x, y in reversed(top)]
    draw.polygon([tuple(s(x, y)) for x, y in top + bottom], fill=color)
    # Main vein from the stalk to near the tip.
    draw.line(s(stalk, middle, stalk + length * 0.82, middle), fill=vein, width=s(3)[0])
    return layer


def bottle():
    layer = Image.new("RGBA", tuple(s(90, 230)), (0, 0, 0, 0))
    draw = ImageDraw.Draw(layer)
    outline = s(5)[0]
    draw.rounded_rectangle(s(31, 0, 59, 22), radius=s(5)[0], fill=TEAL_DARK)  # cap
    draw.rounded_rectangle(s(34, 18, 56, 52), radius=s(4)[0], fill=WHITE, outline=TEAL_DARK, width=outline)  # neck
    draw.rounded_rectangle(s(10, 46, 80, 226), radius=s(22)[0], fill=WHITE, outline=TEAL_DARK, width=outline)  # body
    draw.rectangle(s(12, 110, 78, 156), fill=TEAL_LIGHT)  # label band
    return layer


def newspaper():
    layer = Image.new("RGBA", tuple(s(160, 200)), (0, 0, 0, 0))
    draw = ImageDraw.Draw(layer)
    draw.rounded_rectangle(s(2, 2, 158, 198), radius=s(6)[0], fill=WHITE, outline=TEAL_DARK, width=s(4)[0])
    draw.rectangle(s(18, 20, 142, 38), fill=GRAY500)  # masthead
    draw.rectangle(s(18, 54, 76, 104), fill=TEAL_LIGHT)  # photo
    for y in (56, 70, 84, 98):
        draw.rounded_rectangle(s(86, y, 142, y + 6), radius=s(3)[0], fill=GRAY300)
    for y in (122, 136, 150, 164, 178):
        draw.rounded_rectangle(s(18, y, 142, y + 6), radius=s(3)[0], fill=GRAY300)
    return layer


def main():
    canvas = Image.new("RGBA", tuple(s(WIDTH, HEIGHT)), (0, 0, 0, 0))
    draw = ImageDraw.Draw(canvas)

    # Ground shadow, a darker teal than the background it sits on.
    draw.ellipse(s(150, 505, 650, 548), fill=TEAL_DARK)

    # Bin: tapered body, three grooves, lid and handle.
    draw.polygon([tuple(s(292, 250)), tuple(s(508, 250)), tuple(s(486, 520)), tuple(s(314, 520))], fill=TEAL_LIGHT)
    for x in (350, 400, 450):
        draw.rounded_rectangle(s(x - 7, 290, x + 7, 480), radius=s(7)[0], fill=TEAL)
    draw.rounded_rectangle(s(366, 192, 434, 222), radius=s(12)[0], outline=WHITE, width=s(10)[0])
    draw.rounded_rectangle(s(272, 212, 528, 252), radius=s(12)[0], fill=WHITE)

    paste_rotated(canvas, newspaper(), (205, 430), 10)
    paste_rotated(canvas, bottle(), (608, 418), -14)
    paste_rotated(canvas, leaf(110, 46, WHITE, TEAL), (330, 128), 28)
    paste_rotated(canvas, leaf(84, 36, TEAL_LIGHT, TEAL), (478, 92), -32)
    paste_rotated(canvas, leaf(70, 30, TEAL_DARK, TEAL_LIGHT), (560, 165), 12)

    canvas.resize((WIDTH, HEIGHT), Image.LANCZOS).save(OUTPUT, optimize=True)
    print(f"{OUTPUT} ({WIDTH} x {HEIGHT})")


if __name__ == "__main__":
    main()
