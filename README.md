# Cerberus
<br>
An advanced land protection plugin for PocketMine-MP 5 

Built By Server Owners for Server Owners!

# DEPENDENCIES:
CerberusPM uses [Commando](https://www.github.com/CerberusPM/Commando) (the Link is a Forked Version of Commando which remains UP TO DATE with CerberusPM. 

## Description
**Cerberus** is an essential tool for protecting server structures. It allows server administrators and players to easily protect their builds.


## Permissions & Commands

| **Permission**                                      | **Command**                  | **Description** |
|----------------------------------------------------|-----------------------------|---------------|
| `cerberus.command`                                 | `/cerberus`                  | Base permission for the main command. |
| `cerberus.command.claim`                           | `/cerberus claim`            | Allows claiming land. |
| `cerberus.command.claim.bypass_intersect`         | _N/A_                        | Allows intersecting land claims created by other players. |
| `cerberus.command.claim.count_limit.unlimited`    | `/cerberus claim`            | Bypasses land claim count limit if enabled. |
| `cerberus.command.claim.area_limit.unlimited`     | `/cerberus claim`            | Bypasses land claim area limit if enabled. |
| `cerberus.command.flag`                           | `/cerberus flag`             | Base permission for setting flags. |
| `cerberus.command.help`                           | `/cerberus help`             | Allows access to help documentation. |
| `cerberus.command.here`                           | `/cerberus here`             | Displays land claim information at the player's location. |
| `cerberus.command.info`                           | `/cerberus info`             | Provides information about a specific land claim. |
| `cerberus.command.list`                           | `/cerberus list`             | Lists owned land claims. |
| `cerberus.command.list.other`                     | `/cerberus list <player>`    | Allows listing land claims of other players. |
| `cerberus.command.move`                           | `/cerberus move`             | Moves an existing land claim. |
| `cerberus.command.remove`                         | `/cerberus remove`           | Removes an owned land claim. |
| `cerberus.command.remove.other`                   | `/cerberus remove <name>`    | Allows removing land claims owned by other players. |
| `cerberus.command.selection`                      | `/cerberus pos1`, `/pos2`, `/cerberus move`, `/cerberus expand` | Allows position selection, wand selection, movement, and expansion. |
| `cerberus.command.setspawn`                       | `/cerberus setspawn`         | Sets a spawn point for a land claim. |
| `cerberus.command.setspawn.other`                 | `/cerberus setspawn <name>`  | Allows setting spawn points for land claims owned by others. |
| `cerberus.command.teleport`                       | `/cerberus teleport`         | Teleports to a claim's spawn point. |
| `cerberus.command.teleport.other`                 | `/cerberus teleport <player>`| Allows teleporting other players to spawn points. |
| `cerberus.command.teleport.to.other`              | `/cerberus teleport to <player>` | Allows teleporting to another player's land claim spawn point. |
| `cerberus.command.unsetspawn`                     | `/cerberus unsetspawn`       | Removes a spawn point from a land claim. |
| `cerberus.command.unsetspawn.other`               | `/cerberus unsetspawn <name>`| Allows removing spawn points from land claims owned by others. |
| `cerberus.command.wand`                           | `/cerberus wand`             | Grants access to selection wand tools. |
| `cerberus.command.whitelist`                      | `/cerberus whitelist`        | Grants access to land whitelist management. |
| `cerberus.command.reload`                         | `/cerberus reload`           | Reloads the plugin configuration. |

## Default Permissions
By default, all permissions are set to `op`. This means only server operators have access to these commands unless explicitly assigned to players.


## The Basics:
**This Plugin Aims to Implement features that allow server administrators and players to protect their builds from griefers and even allow players to build with set flags and permissions. 

### How to claim land:
First go to top corner of your claim and type `/cerberus pos1` , then proceed to go to the opposite side of your claim and go to the bottom corner of it and type `/cerberus pos2`. then proceed with `/cerberus claim <land name>`

Video Tutorial:
`Coming in V1.0.0`


# **Work in progress:**

## Features to Implement
flag subcommand,
extend subcommand,
move subcommand

## Authors
This Plugin was Jointly Founded by Levonzie and skyss0fly.
