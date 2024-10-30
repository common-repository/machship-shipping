#  MACHSHIP SHIPPING (Fusedship integration for Woocommerce)
## Available Hooks
Description on how to use existing hooks and their parameters

### machship_set_final_rates
**usage:** `apply_filters('machship_set_final_rates', $rates, $carrierGroupDetails)`
**Description**
- `$rates`: the total and final rates to be shown for calculated shipping
- `$carrierGroupDetails`: Data of selected carrier groups for this quotes
- can overwritten the final rates
- - shows the carrier group details, despatch details and customer's selected location

### machship_product_filter
**usage:** `apply_filters('machship_product_filter', $item)`
**Description**
- `$item`: item in cart
- - this filter is inside of items loop
- - used to view/edit item data in a cart

### machship_product_box_filter
**usage:** `apply_filters('machship_product_box_filter', $item_dimension, $product_id, $qty)`
**Description**
- `$item_dimension`: item dimension details
- `$product_id`: product_id or item id
- `$qty`: item quantity
- - use to set/edit/update the current dimension of the current item

### machship_all_box_filter
**usage:** `apply_filters('machship_all_box_filter', $items)`
**Description**
- `$items`: all items in cart
- - use to create own boxing rules or set the dimension of all items

### machship_warehouses_filter
**usage:** `apply_filters('machship_warehouses_filter', $warehouse_loc)`
**Description**
- `$warehouse_loc`: warehouses location
- - use to set the nearest warehouse locations details in postcode and suburb

### machship_despatchdate_filter
**usage:** `apply_filters('machship_despatchdate_filter', $params)`
**Description**
- `$params`: a field for MachShip to include despatch date
- - primarily use to add despatchDateTimeLocal field in the params

### machship_set_selected_shipping_dest
**usage:** `apply_filters('machship_set_selected_shipping_dest', $toLocation)`
**Description**
- `$toLocation`: an array compose of suburb and postcode of customer
- - use to overwrite/set the suburb and postcode of customer

### machship_set_from_location
**usage:** `apply_filters('machship_set_from_location', $location, $paramsArr)`
**Description**
- `$location`: an array compose of suburb and postcode of customer
- `$paramsArr[$key]` = the parameters of body to send
- - use to overwrite/set the suburb and postcode of customer then set the `fromLocation` field for MachShip


### woo_machship_cron_box_migration
**usage:** `do_action( 'woo_machship_cron_box_migration',  $overwrite, $package_type, $warehouse);`
- `$overwrite` : to overwrite the box data or not ('yes' or 'no')
- `$package_type` : select package type to migrate
```
1 = Carton, 2 = Skid, 3 = Pallet, 4 = Crate, 5 = Satchel, 6 = Roll, 7 = Panel, 8 = Bag,
9 = Tube, 10 = Stillage, 11 = Envelope, 12 = Pack, 13 = Rails, 14 = TimberLoose, 15 = Combined,
16 = TimberPack, 17 = Pipe, 18 = BDouble, 19 = Semi, 20 = TwentyFootContainer, 21 = FortyFootContainer,
22 = Bundle, 23 = Case, 24 = Volume, 25 = CombinedLoadSize, 26 = IBC, 27 = GLPallet, 28 = GLTrolley,
29 = GLCarton, 30 = Trolley, 31 = TotalVolume, 32 = Drum, 33 = Loscam, 34 = LoscamWood, 35 = LoscamPlastic,
36 = Chep, 37 = ChepWood, 38 = ChepPlastic, 39 = Tray, 40 = Pot, 41 = SeedlingRack, 42 = SilverTrolley,
43 = LightTruckTyre, 44 = PassengerTyre, 45 = AgriculturalTyre, 46 = MowerTyre, 47 = SolidTyre,
48 = TractorTyre, 49 = TrailerTyre, 50 = TruckTyre, 51 = Pallecon, 52 = Item, 53 = Machine, 54 = JiffyBag,
55 = Pot200mm, 56 = Pot250mm, 57 = Pot300mm
```
- `$warehouse` : select which warehouse(s) to use

Reference : [CRON WIKI](https://github.com/machship/machship-shipping-wp/wiki/Custom-CRON)


## Guides
- [Upload New Plugin Version Guide](https://github.com/machship/machship-shipping-wp/wiki/How-To-Publish-New-Plugin-Version-to-Wordpress)
- [Create New Release In Github](https://github.com/machship/machship-shipping-wp/wiki/How-To-Create-New-Release-Github-Only)
- [Machship Shipping Testcase](https://github.com/machship/machship-shipping-wp/wiki/Testcase-for-Woo-Machshipping)