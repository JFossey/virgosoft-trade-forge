# 💬 Development Commentary

The following are a development thoughts.

## Git Repo

- The first step was to setup with commit signing and verified commits using SSH keys.
- At first I forgot to change the setting to use squash commits by default, so the first few PR merges used merge commits.
- From about halfway through all PR's were merged with squash commits, my personal preference.
- A branch protection rule was later also setup, but CI/CD was not enforced, for deadline reasons.

## CI/CD

At first, no CI/CD was used. Once I started using PHP Feature tests a github action workflow was setup to run tests.

## Issues

The following are issues that existed at the time of writing this, but might be fixed if time permits.

- At the time of writing this, there is a bug preventing the sale of any assets.
- The way the assets vs the dollar input is priced is questionable, it currently assumes you are entering the price per 1 Asset, and then that is used with the quantity. This feels wrong, and if time permits, I would experiment with doing away with the calc and just having it as a price for x assets.
- Matching only on exact amounts, so it is rarely successful. If time permits, I would have preferred to have the matching of orders to support multiple orders and partial quantities matching.

## Code Quality

Only backend feature tests were used along with `Pint` for some basic code formatting.

If I had more time, I would have preferred to add PHPStan and ESLint.

## Skeleton/Starters

I did not use an advanced opinionated starter project as I felt this would be doing more than what would be asked or needed, or making choices that I would be evaluated on. For this reason, the basic normal Laravel skeleton was used.

## If More Time

- Used explicit model binding
- Form Requests everywhere
- Unit Test of Business Logic
- Front-end Tests
- Split the Trading store into two, on for orders and one for activity
- Improved the UI to show all open offers
- Allow for buying with a single click a open offer
- Add showing expected fees/commissions per order

## Trades Table

The `trades` table was created as a log, even though it would link to associated orders it duplicated as a point in time log amounts and values from the orders that was used to calculate commission.
