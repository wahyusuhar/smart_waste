"""Draws docs/erd.png.

The tables below are copied by hand from web/database/migrations, so update them when a migration changes.
Needs matplotlib, which the training venv already has:

    ml\\.venv\\Scripts\\python docs\\erd.py
"""
from pathlib import Path

import matplotlib

matplotlib.use("Agg")
import matplotlib.pyplot as plt
from matplotlib.patches import Rectangle

ACCENT = "#0f766e"
GRAY900 = "#101828"
GRAY500 = "#6a7282"
GRAY300 = "#d1d5dc"
GRAY100 = "#f3f4f6"

ROW = 2.0
WIDTH = 52.5
TYPE_X = 20.5

# (column, type, key) per table, in migration order.
TABLES = {
    "users": (2, 62, [
        ("id", "bigint unsigned", "PK"),
        ("name", "varchar(255)", ""),
        ("email", "varchar(255)", "UQ"),
        ("phone", "varchar(255) null", ""),
        ("email_verified_at", "timestamp null", ""),
        ("password", "varchar(255)", ""),
        ("role", "enum(admin, user)", ""),
        ("remember_token", "varchar(100) null", ""),
        ("created_at, updated_at", "timestamp null", ""),
    ]),
    "reports": (70, 80, [
        ("id", "bigint unsigned", "PK"),
        ("user_id", "bigint unsigned", "FK"),
        ("description", "text", ""),
        ("photo_path", "varchar(255)", ""),
        ("latitude", "decimal(10,7)", ""),
        ("longitude", "decimal(10,7)", ""),
        ("address", "varchar(255) null", ""),
        ("category", "enum(organic, plastic, paper) null", "IX"),
        ("ai_label", "varchar(255) null", ""),
        ("ai_confidence", "decimal(5,4) null", ""),
        ("status", "enum(pending, in_progress, completed, rejected)", "IX"),
        ("admin_note", "text null", ""),
        ("created_at", "timestamp null", "IX"),
        ("updated_at", "timestamp null", ""),
    ]),
    "report_status_logs": (70, 44, [
        ("id", "bigint unsigned", "PK"),
        ("report_id", "bigint unsigned", "FK"),
        ("changed_by", "bigint unsigned null", "FK"),
        ("from_status", "enum(...) null", ""),
        ("to_status", "enum(...)", ""),
        ("note", "text null", ""),
        ("created_at", "timestamp", ""),
    ]),
    "personal_access_tokens": (2, 36, [
        ("id", "bigint unsigned", "PK"),
        ("tokenable_type", "varchar(255)", "IX"),
        ("tokenable_id", "bigint unsigned", "IX"),
        ("name", "text", ""),
        ("token", "varchar(64)", "UQ"),
        ("abilities", "text null", ""),
        ("last_used_at", "timestamp null", ""),
        ("expires_at", "timestamp null", "IX"),
        ("created_at, updated_at", "timestamp null", ""),
    ]),
    "sessions": (70, 22, [
        ("id", "varchar(255)", "PK"),
        ("user_id", "bigint unsigned null", "IX"),
        ("ip_address", "varchar(45) null", ""),
        ("user_agent", "text null", ""),
        ("payload", "longtext", ""),
        ("last_activity", "int", "IX"),
    ]),
}


def row_y(table, column):
    x, top, columns = TABLES[table]
    index = [c[0] for c in columns].index(column)
    return top - ROW * (index + 1.5)


def draw_table(ax, name):
    x, top, columns = TABLES[name]
    height = ROW * (len(columns) + 1)
    ax.add_patch(Rectangle((x, top - height), WIDTH, height, facecolor="white", edgecolor=GRAY300, linewidth=1))
    ax.add_patch(Rectangle((x, top - ROW), WIDTH, ROW, facecolor=GRAY100, edgecolor=GRAY300, linewidth=1))
    ax.text(x + 0.8, top - ROW / 2, name, va="center", fontsize=9.5, fontweight="bold", color=GRAY900)
    for i, (column, kind, key) in enumerate(columns):
        y = top - ROW * (i + 1.5)
        ax.text(x + 0.8, y, key, va="center", fontsize=7, fontweight="bold", color=ACCENT if key in ("PK", "FK") else GRAY500)
        ax.text(x + 3.6, y, column, va="center", fontsize=8, color=GRAY900)
        ax.text(x + TYPE_X, y, kind, va="center", fontsize=7, color=GRAY500)


def side(table, via_x):
    """x of the table edge that faces via_x."""
    x = TABLES[table][0]
    return x + WIDTH if via_x > x + WIDTH / 2 else x


def relation(ax, start, end, via_x, label, label_xy, ha="left", dashed=False):
    """One-to-many line from start (table, column) on the "1" side to end on the "N" side."""
    (t1, c1), (t2, c2) = start, end
    x1, x2 = side(t1, via_x), side(t2, via_x)
    y1, y2 = row_y(t1, c1), row_y(t2, c2)
    style = dict(color=GRAY500, linewidth=1, linestyle=(0, (4, 3)) if dashed else "-")
    ax.plot([x1, via_x, via_x, x2], [y1, y1, y2, y2], **style)
    for x, y, mark in ((x1, y1, "1"), (x2, y2, "N")):
        ax.text(x + (-1.4 if x > via_x else 0.4), y + 0.5, mark, fontsize=7.5, color=GRAY900)
    ax.text(*label_xy, label, fontsize=6.5, color=GRAY500, va="center", ha=ha)


fig, ax = plt.subplots(figsize=(16, 9.4))
ax.set_xlim(-14, 139)
ax.set_ylim(0, 82)
ax.axis("off")

for table in TABLES:
    draw_table(ax, table)

relation(ax, ("users", "id"), ("reports", "user_id"), 66, "ON DELETE CASCADE", (65.4, 72), ha="right")
relation(ax, ("users", "id"), ("report_status_logs", "changed_by"), 61, "ON DELETE\nSET NULL", (61.6, 34.6))
relation(ax, ("users", "id"), ("sessions", "user_id"), 56, "tanpa foreign key", (56.6, 17.8), dashed=True)
relation(ax, ("users", "id"), ("personal_access_tokens", "tokenable_id"), -3,
         "polymorphic\n(Sanctum),\ntanpa\nforeign key", (-3.6, 44), ha="right", dashed=True)
relation(ax, ("reports", "id"), ("report_status_logs", "report_id"), 128.5, "ON DELETE\nCASCADE", (129.1, 56))

ax.text(2, 1.2, "PK primary key   FK foreign key   UQ unique   IX index.   Tabel bawaan Laravel lain (cache, cache_locks, jobs, "
        "job_batches, failed_jobs, password_reset_tokens, migrations) tidak digambar; lihat docs/schema.sql.",
        fontsize=7.5, color=GRAY500)

fig.savefig(Path(__file__).with_name("erd.png"), dpi=150, bbox_inches="tight", facecolor="white")
