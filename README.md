# Facility Booking API

A simple sports facility booking/reservation API.

This is the back-end implementation of my pet project. The front-end code is [here](https://github.com/alven-da/facility-booking-web).

Created using the following stack:

- PHP with Slim Framework 4 - for lightweight API implementation
- [PayMongo Gateway](https://paymongo.com) - to establish a payment for reservation
- Google Calendar API - For date and time slot booking
- MySQL - for saving reservation and membership

Environment variable to use as follows:

```
APPLICATION_NAME=
CALENDAR_ID=
PAYMONGO_PUBLIC_KEY=
PAYMONGO_SECRET_KEY=
PAYMONGO_API_BASE_URL=
ENVIRONMENT=
```

### API Documentation

A Postman collection is included with this project for testing each API

- [GET] `/api/health` - The health check API. Returns `ok` if server is accessible

- [GET] `/api/calendar?selectedDate={YYYY-MM-DD}` - returns time slots based on selected dates

- [POST] `/api/prepayment` - creates a checkout request based on API and returns a checkout url that will be used to redirect to payment page

- [POST] `/api/validate` - this endpoint will be called after a successful payment has been made from front-end. It checks the `confirmationId` if existing in the database (which means that there is a TENTATIVE booking already in placed). If matched in the database, it will be updated from TENTATIVE to CONFIRMED. It returns the reservation details i.e. `confirmationId`, `paymentReference`, `date` and `time` of booking

- [GET] `/api/member/{memberId}` - a membership lookup endpoint, search a member by ID
