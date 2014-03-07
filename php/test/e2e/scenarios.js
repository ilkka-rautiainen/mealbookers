'use strict';

/* http://docs.angularjs.org/guide/dev_guide.e2e-testing */

angular.scenario.matcher('urlEqualsFuture', function(expectedFuture) {
  // Parse the leading '/' (by default, this.actual is e.g. /Test0, when expectedFuture.value is Test0)
  return this.actual.substring(1) === expectedFuture.value;
});


describe('Mealbookers', function() {

  beforeEach(function() {
    browser().navigateTo('/');
  });


  it('should automatically redirect to /app/menu when location hash/fragment is empty', function() {
    expect(browser().location().url()).toContain('/app/menu');
  });


  describe('menu', function() {
    beforeEach(function() {
      browser().navigateTo('#/app/menu');
    });

    it('should render restaurant list', function() {
      expect(element('#menuContainer .restaurant').count()).toBeGreaterThan(0);
    });

    it('should put meals to Alvari', function() {
      if (!(new Date().getDay() == 0 || new Date().getDay() == 6)) {
        expect(element('#menuContainer .restaurant .meal-group .meal-row').count()).toBeGreaterThan(0);
      }
    });

  });

});
